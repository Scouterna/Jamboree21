import "./App.css";
import * as React from "react";
import { useState, useEffect } from "react";
import {
  DataGridPremium,
  svSE,
  GridToolbarContainer,
  GridToolbarExport,
  GridToolbarQuickFilter,
  GridToolbarFilterButton,
} from "@mui/x-data-grid-premium";
import AddIcon from "@mui/icons-material/Add";
import Button from "@mui/material/Button";
import TextField from "@mui/material/TextField";
import Dialog from "@mui/material/Dialog";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import DialogTitle from "@mui/material/DialogTitle";
import InputLabel from "@mui/material/InputLabel";
import MenuItem from "@mui/material/MenuItem";
import Select from "@mui/material/Select";

function Cardholders() {
  const [tableData, setTableData] = useState([]);

  function loadTable() {
    fetch("/api/categories")
      .then((response) => response.json())
      .then((data) => setTableData(data));
  }

  useEffect(() => {
    loadTable();
  }, []);

  const savetoDB = (newRow, oldRow) => {
    let bodydata = {
      table: "cardcategories",
      row: newRow,
    };

    fetch("/api/update", {
      method: "post",
      body: JSON.stringify(bodydata),
    });
    return { ...newRow };
  };

  const defaultValues = {
    merchant_category: "",
    accounting_category: "Okategoriserat",
  };

  const [formValues, setFormValues] = useState(defaultValues);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormValues({
      ...formValues,
      [name]: value,
    });
  };

  const [open, setOpen] = React.useState(false);

  const handleClickOpen = () => {
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
  };

  const handleSubmit = (event) => {
    event.preventDefault();
    let bodydata = {
      table: "cardcategories",
      row: formValues,
    };
    fetch("/api/add", {
      method: "post",
      body: JSON.stringify(bodydata),
    })
      .then((data) => loadTable())
      .then((data) => setOpen(false));
  };

  const columns = [
    { field: "id", headerName: "id", type: "integer" },
    {
      field: "merchant_category",
      headerName: "Kategori Auto",
      type: "string",
      editable: true,
    },
    {
      field: "accounting_category",
      headerName: "Kategori Bokföring",
      type: "singleSelect",
      valueOptions: [
        "4230 Resor",
        "4240 Kost",
        "5860 Logi",
        "4260 Aktiviteter",
        "6210 Telefon",
        "6590 Övriga kostnader",
        "6998 Kontantuttag",
        "6590 Okategoriserat",
      ],
      editable: true,
    },
  ];

  function CustomToolbar() {
    return (
      <GridToolbarContainer>
        <GridToolbarFilterButton />
        <GridToolbarExport />
        <Button
          color="primary"
          startIcon={<AddIcon />}
          onClick={handleClickOpen}
        >
          Lägg till
        </Button>
        <GridToolbarQuickFilter sx={{ marginLeft: "auto" }} />
      </GridToolbarContainer>
    );
  }

  return (
    <div
      style={{
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        height: "85vh",
        margin: 40,
      }}
    >
      <Dialog open={open} onClose={handleClose}>
        <form onSubmit={handleSubmit}>
          <DialogTitle>Lägg till kategori</DialogTitle>
          <DialogContent sx={{ minWidth: 500 }}>
            <TextField
              autoFocus
              margin="dense"
              id="merchant_category"
              label="Wise Merchant Category"
              type="text"
              fullWidth
              variant="standard"
              name="merchant_category"
              value={formValues.merchant_category}
              onChange={handleChange}
            />
            <br />
            <br />
            <InputLabel id="select-label">Välj Kategori</InputLabel>
            <Select
              labelId="select-label"
              id="accounting_category"
              value={formValues.accounting_category}
              label="Välj Kategori"
              fullWidth
              onChange={handleChange}
              name="accounting_category"
            >
              <MenuItem value={"4230 Resor"}>4230 Resor</MenuItem>
              <MenuItem value={"4240 Kost"}>4240 Kost</MenuItem>
              <MenuItem value={"5860 Logi"}>5860 Logi</MenuItem>
              <MenuItem value={"4260 Aktiviteter"}>4260 Aktiviteter</MenuItem>
              <MenuItem value={"6210 Telefon"}>6210 Telefon</MenuItem>
              <MenuItem value={"6590 Övriga kostnader"}>
                6590 Övriga kostnader
              </MenuItem>
              <MenuItem value={"6998 Kontantuttag"}>Kontantuttag</MenuItem>
              <MenuItem value={"6590 Okategoriserat"}>Okategoriserat</MenuItem>
            </Select>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Avbryt</Button>
            <Button type="submit">Lägg till</Button>
          </DialogActions>
        </form>
      </Dialog>
      <DataGridPremium
        initialState={{
          columns: {
            columnVisibilityModel: {
              id: false,
            },
          },
        }}
        localeText={svSE.components.MuiDataGrid.defaultProps.localeText}
        sx={{ fontSize: 9 }}
        rows={tableData}
        columns={columns}
        density="compact"
        components={{ Toolbar: CustomToolbar }}
        disableColumnSelector
        disableDensitySelector
        componentsProps={{
          toolbar: {
            showQuickFilter: true,
            quickFilterProps: { debounceMs: 500 },
          },
        }}
        processRowUpdate={savetoDB}
        experimentalFeatures={{
          newEditingApi: true,
        }}
      />
    </div>
  );
}

export default Cardholders;
