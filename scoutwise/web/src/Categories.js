import './App.css';
import * as React from "react";
import { useState,useEffect } from "react";
import { DataGridPremium, GridColDef, GridToolbar, svSE } from "@mui/x-data-grid-premium";

function Cardholders() {

	  const [tableData,setTableData]=useState([]);

	  useEffect(()=>{
		fetch("/api/categories")
			.then((response) => response.json())
			.then((data) => setTableData(data))
		}, []);

	  const savetoDB = (newRow,oldRow) =>{

		  let bodydata = {
			"table":"cardcategories",
			"row":newRow
		  }

		  fetch("/api/update", {
			method: "post",
			body: JSON.stringify(bodydata)
		  })
		  return {...newRow};
		};

	  const columns: GridColDef[] = [
		{ field: 'id', headerName: 'id', type:'integer', },
		{ field: 'merchant_category', headerName: 'Kategori Auto', type:'string', editable: false},
		{ field: 'accounting_category', headerName: 'Kategori Bokföring', type:'singleSelect', valueOptions: [
			'4230 Resor',
			'4240 Kost',
			'5860 Logi',
			'4260 Aktiviteter',
			'6210 Telefon',
			'6590 Övriga kostnader',
			'Kontantuttag',
			'Okategoriserat'
		  ], editable: true},
	  ];

	  return (
		<div style={{
			display: 'flex',
			alignItems: 'center',
			justifyContent: 'center',
			height: '85vh',
			margin:40 }}>
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
			newEditingApi: true }}
		   />

	   </div>
	  );
}



export default Cardholders;