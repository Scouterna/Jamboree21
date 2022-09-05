<script>
    export let row;
    export let col;
    export let choices = undefined;
    export let default_value = undefined;
</script>

<input bind:value={row['questions'][col.key]}>