<script>
    export let row;
    export let onSaveButtonClick;
    export let col;
  </script>

  <button on:click={() => onSaveButtonClick(row)}
    >Spara
  </button>