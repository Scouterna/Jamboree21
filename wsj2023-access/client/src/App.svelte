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
  let filter;
  let filter_value = 0;

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
        props: { key:'Spara', onSaveButtonClick },
      },
    },
  ];

  function get_status_columns(questions) {
    let columns = [];
    for (let q in questions) {
      if (questions[q].status) {
        columns.push({
          key: questions[q].id,
          title: questions[q].question,
          sortable: true,
          renderComponent: {
            component: questions[q].type == 'choice' ? StatusSelector : StatusNumber,
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
    filter = 0;
    filter_value = 0;
    questions = await fetch_async('/questions?form_id='+selected_form);
    status_columns = get_status_columns(questions);
    participants = fetch_async("/participants?form="+selected_form+"&page="+query_page+"&q="+(filter ? filter.id : '0')+"&q_val="+filter_value);
  }

  async function selectPage(p) {
    query_page = p;
    participants = fetch_async("/participants?form="+selected_form+"&page="+query_page+"&q="+(filter ? filter.id : '0')+"&q_val="+filter_value);
  }

  async function select_filter() {
    query_page = 1;
    participants = fetch_async("/participants?form="+selected_form+"&page="+query_page+"&q="+(filter ? (typeof(filter) == 'string' ? filter : filter.id) : '0')+"&q_val="+filter_value);
  }

  let _tab_id = -1
  function show_tab(tab_id) {
    if (tab_id == _tab_id) return false;
    _tab_id = tab_id;
    return true;
  }

  let _section_id = -1
  function show_section(section_id) {
    if (section_id == _section_id) return false;
    _section_id = section_id;
    return true;
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
      <button on:click={() => selectPage(query_page-1)}>&lt; Föregående</button>
    {/if}
    {#if (rows.items && rows.size < rows.total) }
      {#each [...Array(Math.floor(rows.total / rows.size) + 1).keys()] as p}
        <button on:click={() => selectPage(p+1)} class="{(rows.page == p+1) ? 'btn-primary' : ''}">
          {p + 1}
        </button>
      {/each}
    {/if}
    {#if rows.total > (rows.page * rows.size)}
      <button on:click={() => selectPage(query_page+1)}>Nästa &gt;</button>
    {/if}
  </div>

  {#if rows.items.length > 0}

  <div> Filter:
  <select bind:value={filter} on:change="{() => filter_value = 0}">
    <option value='0'></option>
    <option value='member_no'>Medlemsnummer</option>
    <option value='first_name'>Förnamn</option>
    <option value='last_name'>Efternamn</option>
    {#each questions as q}{#if q.filterable}
      <option value={q}>{q.question}</option>
    {/if}{/each}
  </select>

  {#if typeof(filter) == 'string'}
    <input bind:value={filter_value} />
  {:else if filter != 0}
    <select bind:value={filter_value}>
      <option value='0'></option>
      {#each Object.entries(filter.choices) as [id, fv]}
        <option value={id}>{fv.option}</option>
      {/each}
    </select>
  {/if}
  {#if filter_value}
    <button on:click={() => select_filter()}>Filtrera &gt;</button>
  {/if}
  </div>

  <div>
  <SvelteTable
  columns={[...columns, ...status_columns, ...save_button]}
  rows={rows.items}
  showExpandIcon={true}
  classNameTable="table table-striped"
  classNameThead="table-primary"
  expandRowKey="member_no">
    <div slot="expanded" let:row>
      <table class="table table-bordered">
        <tr>
          <th colspan="2">
            <h4>Grundläggande info i scoutnet</h4>
          </th>
        </tr>
        {#if row.cancelled_date != null}
          <tr>
            <th>Avanmäld</th>
            <td>{row.cancelled_date}</td>
          </tr>
        {/if}
        <tr>
          <th>Födelsedatum</th>
          <td>{row.date_of_birth}</td>
        </tr>
        <tr>
          <th>Epost i scoutnet</th>
          <td>{row.primary_email}</td>
        </tr>
      {#each questions as q, i}{#if q.id in row.questions}
          {#if show_tab(q.tab_id) && q.tab_title != null && q.tab_title != ''}
            <tr>
              <th colspan="2">
                <h2>{q.tab_title}</h2>
              </th>
            </tr>
          {/if}
          {#if show_section(q.section_id) && q.section_title != null && q.section_title != ''}
            <tr>
              <th colspan="2">
                <h4>{q.section_title}</h4>
              </th>
            </tr>
          {/if}
          <tr>
            <th>{q.question}</th>
            <td>
              {#if q.type == 'choice' }
                {getChoiceValues(q, row.questions[q.id])}
              {:else if q.type == 'boolean'}
                {row.questions[q.id] == 0 ? 'Nej' : 'Ja'}
              {:else if q.type == 'other_unsupported_by_api' && row.questions[q.id].includes('linked_id')}
                {JSON.parse(row.questions[q.id])['value']}

              {:else}
                {row.questions[q.id]}
              {/if}
            </td>
          </tr>
        {/if}{/each}
      </table>
    </div>
  </SvelteTable>
</div>
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