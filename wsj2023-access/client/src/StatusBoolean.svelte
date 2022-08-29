<script>
    export let row;
    export let col;
  </script>

<input bind:value={row['questions'][col.key]}>