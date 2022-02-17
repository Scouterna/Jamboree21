<script>
    export let row;
    export let col;
    export let choices;
    export let default_value;
  </script>

<input bind:value={row['questions'][col.key]}>