import './App.css';
import * as React from "react";
import { useState,useEffect } from "react";
import { DataGridPremium, GridColDef, GridToolbar, svSE, useGridApiRef } from "@mui/x-data-grid-premium";
import ReactCountryFlag from "react-country-flag"
import CloseIcon from '@mui/icons-material/Close';
import ReceiptLongIcon from '@mui/icons-material/ReceiptLong';

function Wisetable() {


  const apiRef = useGridApiRef();
  const [tableData,setTableData]=useState([]);

  useEffect(()=>{
    var heads = {}
    try {
      const cred = JSON.parse(localStorage.getItem("credentials"));
      const apikey = cred['access_token'];
      heads = {'Authorization':'Bearer '+apikey,  'Content-Type': 'application/json'}
    }
    catch {
      heads = {}
    }

    fetch("/api/transactions", { headers:heads})
        .then((response) => response.json())
        .then((data) => setTableData(data))
    }, []);

  function getCategory(params) {
    if (params.row.accounting_category_override === "") { return params.row.accounting_category; }
    else { return params.row.accounting_category_override; }

  }

  function getIcon(params){
    if (params.aggregation) { return params.formattedValue;}
    else {
      if (params.value === 1) {
        return <CloseIcon color="disabled" />;
      }
      return <ReceiptLongIcon color="disabled" />;
    }
  }

  function getFlag(params) {
     return <ReactCountryFlag countryCode={params.value} title={params.value}
      style={{
          width: '1.5em',
          height: '1.2em',
      }}
      svg />;

  }

  function getDate(params) {
    var date = new Date(params.row.date);
    if(!isNaN(date.getTime())) {
      return date;
    }
    else { return ""; }
  }

  const savetoDB = (newRow,oldRow) =>{

      let bodydata = {
        "table":"cardtransactions",
        "row":newRow
      }

      fetch("/api/update", {
        method: "post",
        body: JSON.stringify(bodydata)
      })
      return {...newRow};
    };

  const columns: GridColDef[] = [
    { field: 'id', headerName: 'id', type:'number', },
    { field: 'date', headerName: 'Datum', type:'dateTime', valueGetter: getDate},
    { field: 'unit', headerName: 'Avdelning', type:'string'},
    { field: 'amount_sek', headerName: 'Summa (SEK)', type:'number' },
    { field: 'merchant', headerName: 'Försäljningsställe', type:'string' },
    { field: 'country', headerName: 'Land', type:'string', renderCell: getFlag},
    { field: 'merchant_category', headerName: 'Kategori (auto)', type:'string'},
    { field: 'name', headerName: 'Namn', type:'string' },
    { field: 'note', headerName: 'Notering', type:'string' },
    { field: 'admin_note', headerName: 'Admin Notering', type:'string', editable: true },
    { field: 'accounting_category', headerName: 'Kategori (auto bokföring)', type:'singleSelect', valueOptions: [
      '4230 Resor',
      '4240 Kost',
      '5860 Logi',
      '4260 Aktiviteter',
      '6210 Telefon',
      '6590 Övriga kostnader',
      'Kontantuttag',
      'Okategoriserat'
    ],editable:false},
    { field: 'accounting_category_override', headerName: 'Kategori (bokföring)', type:'singleSelect', valueOptions: [
      '4230 Resor',
      '4240 Kost',
      '5860 Logi',
      '4260 Aktiviteter',
      '6210 Telefon',
      '6590 Övriga kostnader',
      'Kontantuttag',
      'Okategoriserat'
    ],editable:true,
    valueGetter: getCategory,
  groupingValueGetter: getCategory},
    { field: 'missingreceipt', headerName: 'Saknas kvitto?', type:'number', renderCell: getIcon
  }
  ];

  return (
    <div style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        height: '85vh',
        margin:40 }}>

    <DataGridPremium
      apiRef={apiRef}
      initialState={{
        columns: {
          columnVisibilityModel: {
            id: false,
            accounting_category: false
          },
        },
        aggregation: {
          model: {
            amount_sek: 'sum',
            missingreceipt: 'sum',
          },
        }
      }}
      localeText={svSE.components.MuiDataGrid.defaultProps.localeText}
      sx={{ fontSize: 9 }}
      rows={tableData}
      columns={columns}
      density="compact"
      components={{ Toolbar: GridToolbar}}
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
        aggregation: true,
        newEditingApi: true }}
       />
   </div>
  );
}



export default Wisetable;
