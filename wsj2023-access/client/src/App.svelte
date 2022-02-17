<script>
  import SvelteTable from "svelte-table";
  import StatusSelector from "./StatusSelector.svelte";
  import StatusNumber from "./StatusNumber.svelte";

  let participants = Promise.resolve({items:[]});
  $: questions = Promise.resolve([]);
  let status_columns = [];
  let page = 1;
  let selected_form;

  let forms_promise = fetch_forms();

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
      res = question['choices'][choices]['option'];
    }
    return res;
  }

  function getDefaultValue(question) {
    if (questions) return question['default_value']
    return ''
  }

  function get_status_columns(questions) {
    let columns = [];
    for (const [q, value] of Object.entries(questions)) {
      if (value.status) {
        let component = value.type == 'choice' ? StatusSelector : StatusNumber
        columns.push({
          key: q,
          title: value.question,
          sortable: true,
          renderComponent: {
            component: component,
            props: {
              choices: questions[q]['choices'],
              default_value: questions[q]['default_value']
            },
          },
          //  v.questions[q] // getChoiceValue(q, v.questions[q] == null ? getDefaultValue(q) : v.questions[q])
        })
      }
    }
    return columns;
  }

  async function fetch_forms() {
    const res = await fetch('/forms');

    if (res.ok) {
      return res.json()
    } else {
      throw new Error(res.text())
    }
  }

  async function fetch_questions() {
    const res = await fetch('/questions?form='+selected_form);

    if (res.ok) {
      return res.json()
    } else {
      throw new Error(res.text())
    }
  }

  async function fetch_participants() {
    const res = await fetch("/participants?form="+selected_form+"&page="+page);

    if (res.ok) {
      return res.json();
    } else {
      throw new Error(res.text());
    }
  }

  async function select_form(form_id) {
    page = 1;
    selected_form = form_id;
    questions = await fetch_questions(form_id);
    status_columns = get_status_columns(questions);
    participants = fetch_participants(form_id);
  }

  async function selectPage(p) {
    page = p;
    participants = fetch_participants();
  }

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
    <button on:click={() => selectPage(page-1)}>&lt; prev</button>
  {/if}
  {#if (rows.items && rows.size < rows.total) }
    {#each [...Array(Math.floor(rows.total / rows.size) + 1).keys()] as p}
      <button on:click={() => selectPage(p+1)} class="{(rows.page == p+1) ? 'btn-primary' : ''}">
        {p + 1}
      </button>
    {/each}
  {/if}
  {#if rows.total > (rows.page * rows.size)}
    <button on:click={() => selectPage(page+1)}>Next page &gt;</button>
  {/if}
  </div>
  {#if rows.items.length > 0}
  <SvelteTable
  columns={[...columns, ...status_columns]}
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
          <td q='{q_id}' q_t='{questions[q_id]['type']}'>{questions[q_id] == null ? 'Okänd fråga '+q_id : questions[q_id]['question']}</td>
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