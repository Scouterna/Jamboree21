<script>
  export let row;
  export let col;
  export let choices;
  export let default_value;
</script>

<select value={row.questions[col.key] ? row.questions[col.key] : default_value}>
    {#each Object.entries(choices) as [id, value]}}
        <option value={id}>
            {value.option}
        </option>
    {/each}
</select>
