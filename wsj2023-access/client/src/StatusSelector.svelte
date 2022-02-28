<script>
  export let row;
  export let col;
  export let choices;
  export let default_value;

  if (!row.questions[col.key])
    row.questions[col.key] = default_value
</script>

<select bind:value={row.questions[col.key]}>
    {#each Object.entries(choices) as [id, value]}}
        <option value={id}>
            {value.option}
        </option>
    {/each}
</select>
