<script>
  import SvelteTable from "svelte-table";
  import { onMount } from 'svelte';
  export let type;
  export let rows = [];
  export let questions = {};
  export let status_columns = [];

  onMount(async () => {
    const responseQ = await fetch('/questions?type='+type);
    const jsonQ = await responseQ.json();
    questions = jsonQ;

    const responseS = await fetch('/internal_statuses?type='+type);
    const jsonS = await responseS.json();
    status_columns = set_status_columns(jsonS);

    const response = await fetch("/participants?type="+type);
    const json = await response.json();
    rows = json.participants;
  })

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
      key: "registration_date",
      title: "Anmäld",
      sortable: true,
      value: v => v.registration_date + (v.cancelled_date == null ? '' : ' (Avanmäld)')
    },
    {
      key: "date_of_birth",
      title: "Födelsedatum",
      sortable: true,
      value: v => v.date_of_birth
    }
  ];

  function getChoiceValue(question, choice) {
    if (questions) return questions[question]['choices'][choice]['option']
    return ''
  }

  function getDefaultValue(question) {
    if (questions) return questions[question]['default_value']
    return ''
  }

  function set_status_columns(json) {
    let columns = [];
    for (const [q, value] of Object.entries(json)) {
      columns.push({
        key: q,
        title: value.question,
        sortable: true,
        value: v => getChoiceValue(q, v.questions[q] == null ? getDefaultValue(q) : v.questions[q])
      })
    }
    return columns;
  }
</script>

<svelte:head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
</svelte:head>

<main>
	<h1>WSJ2023 Deltagarvyer</h1>
	<p>Besök <a href="https://scoutnet.se/activities/view/1758">arrangemanget</a> för mer information</p>
</main>

<SvelteTable
  columns={[...columns, ...status_columns]}
  rows={rows}
  showExpandIcon={true}
  classNameTable="table table-striped table-hover"
  classNameThead="table-primary"
  expandRowKey="member_no">
    <div slot="expanded" let:row class="text-center">
      {row.cancelled_date == null ? '' : ("Avanmäld: " + row.cancelled_date)}
      Mejl: {row.primary_email}
    </div>
</SvelteTable>

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