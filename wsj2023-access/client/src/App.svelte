<script>
  import SvelteTable from "svelte-table";
  import StatusSelector from "./StatusSelector.svelte";
  import StatusNumber from "./StatusNumber.svelte";
  import SaveButtonComponent from "./SaveButtonComponent.svelte";

  let participants = Promise.resolve({items:[]});
  let status_columns = [];
  let query_page = 1;
  let selected_form;
  $: questions = Promise.resolve([]);
  let forms_promise = Promise.resolve([]);

  const columns = [
    {
      key: "member_no",
      title: "Nr",
      sortable: true,
      value: v => v.member_no
    },
    {
      key: "first_name",
      title: "Förnamn",
      sortable: true,
      value: v => v.first_name //+ " (" + printSex(v.sex) + ")"
    },
    {
      key: "last_name",
      title: "Efternamn",
      sortable: true,
      value: v => v.last_name
    },
    {
      key: "date_of_birth",
      title: "Födelsedatum",
      sortable: true,
      value: v => v.date_of_birth
    },
    {
      key: "registration_date",
      title: "Anmäld",
      sortable: true,
      value: v => v.registration_date + (v.cancelled_date == null ? '' : ' (Avanmäld)')
    },
  ];

  const save_button = [
    {
      key: "save",
      title: "",
      sortable: false,
      renderComponent: {
        component: SaveButtonComponent,
        props: { onSaveButtonClick },
      },
    },
  ];

  function get_status_columns(questions) {
    let columns = [];
    for (const [q, value] of Object.entries(questions)) {
      if (value.status) {
        columns.push({
          key: q,
          title: value.question,
          sortable: true,
          renderComponent: {
            component: value.type == 'choice' ? StatusSelector : StatusNumber,
            props: {
              choices: questions[q]['choices'],
              default_value: questions[q]['default_value']
            },
          },
        })
      }
    }
    return columns;
  }

  function getChoiceValues(question, choices) {
    let res = '';
    if (choices == null)
      if (question['default_value'] in question['choices'])
        return question['choices'][question['default_value']]['option'];
      else
        return '';
    if (Array.isArray(choices)) {
      choices.forEach(choice => { res += question['choices'][choice]['option'] });
    } else {
      if (choices in question['choices'])
        res = question['choices'][choices]['option'];
      else
        res = ''
    }
    return res;
  }

  async function post_answers(member_no, answers) {
    const res = await fetch('/update_status?member_no='+member_no, {
      method: 'POST',
      body: JSON.stringify(answers),
      headers: {"content-type": "application/json"},
    });

    if (res.ok) {
      return res.json()
    } else {
      throw new Error(res.text())
    }
  }

  async function onSaveButtonClick(row) {
    console.log(row);
    let status_answers = {};
    Object.keys(status_columns).forEach(key => {
      status_answers[status_columns[key].key] = row.questions[status_columns[key].key];
      // console.log(`${ status_columns[key].title } (${status_columns[key].key}) = ${row.questions[status_columns[key].key]}`);
    });
    post_answers(row.member_no, status_answers);
  }

  async function fetch_async(path) {
    const res = await fetch(path);

    if (res.ok) {
      return res.json()
    } else {
      throw new Error(res.text())
    }
  }

  async function select_form(form_id) {
    query_page = 1;
    selected_form = form_id;
    questions = await fetch_async('/questions?form='+selected_form);
    status_columns = get_status_columns(questions);
    participants = fetch_async("/participants?form="+selected_form+"&page="+query_page);
  }

  async function selectPage(p) {
    query_page = p;
    participants = fetch_async("/participants?form="+selected_form+"&page="+query_page);
  }

  forms_promise = fetch_async("/forms");
</script>

<svelte:head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
</svelte:head>

<main>
	<h1>Scoutnet aktivitetsvyer</h1>
  {#await forms_promise then forms}
    {#each Object.entries(forms) as [id, name]}
      <button on:click={() => select_form(id)} class="{(selected_form == id) ? 'btn-primary' : ''}">{name}</button>
    {/each}
  {/await}
</main>


{#await participants}
	<p>...Hämtar data...</p>
{:then rows}
  <div class='text-center'>
  {#if rows.page > 1}
    <button on:click={() => selectPage(query_page-1)}>&lt; prev</button>
  {/if}
  {#if (rows.items && rows.size < rows.total) }
    {#each [...Array(Math.floor(rows.total / rows.size) + 1).keys()] as p}
      <button on:click={() => selectPage(p+1)} class="{(rows.page == p+1) ? 'btn-primary' : ''}">
        {p + 1}
      </button>
    {/each}
  {/if}
  {#if rows.total > (rows.page * rows.size)}
    <button on:click={() => selectPage(query_page+1)}>Next page &gt;</button>
  {/if}
  </div>
  {#if rows.items.length > 0}
  <SvelteTable
  columns={[...columns, ...status_columns, ...save_button]}
  rows={rows.items}
  showExpandIcon={true}
  classNameTable="table table-striped"
  classNameThead="table-primary"
  expandRowKey="member_no">
    <div slot="expanded" let:row>
      <table class="table table-bordered">
        {#each Object.entries(row.questions) as [q_id, value]}
        <!-- remove status -->
        <tr>
          <td q='{q_id}'>{questions[q_id] == null ? 'Okänd fråga '+q_id : questions[q_id]['question']}</td>
          <td>
            {#if questions[q_id] == null }
              {value}
            {:else if questions[q_id]['type'] == 'choice' }
              {getChoiceValues(questions[q_id], value)}
            {:else if questions[q_id]['type'] == 'text'}
              {value}
            {:else if questions[q_id]['type'] == 'boolean'}
              {value == 0 ? 'Nej' : 'Ja'}
            {:else if questions[q_id]['type'] == 'other_unsupported_by_api' && value.includes('linked_id')}
              {JSON.parse(value)['value']}
            {:else}
              {value} (type: {questions[q_id]['type']}}
            {/if}
          </td>
        </tr>
        {/each}
      </table>
      {row.cancelled_date == null ? '' : ("Avanmäld: " + row.cancelled_date)}
    </div>
  </SvelteTable>
  {/if}
{:catch error}
	<p style="color: red">{error.message}</p>
{/await}

<style>
	main {
		text-align: center;
		padding: 1em;
		max-width: 240px;
		margin: 0 auto;
	}

	h1 {
		color: #ff3e00;
		text-transform: uppercase;
		font-size: 4em;
		font-weight: 100;
	}

	@media (min-width: 640px) {
		main {
			max-width: none;
		}
	}
</style>