<script>
  export let row;
  export let col;
  export let choices = undefined;
  export let default_value;

  let checked = row['questions'][col.key] == undefined ? default_value : row['questions'][col.key]

  function handleClick() {
    row['questions'][col.key] = checked ? 1 : 0
  }
</script>

<input type=checkbox bind:checked on:change={() => handleClick()} />
