import './App.css';
import * as React from "react";
import { useState,useEffect,useCallback } from "react";
import { useParams } from "react-router-dom";
import {
  DataGridPremium,
  GridActionsCellItem,
  GridRowModes,
  GridToolbar,
  svSE } from "@mui/x-data-grid-premium";
import moment from 'moment';
import { styled } from '@mui/material/styles';
import Snackbar from '@mui/material/Snackbar';
import Alert from '@mui/material/Alert';

import Box from '@mui/material/Box';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableRow from '@mui/material/TableRow';
import TableCell from '@mui/material/TableCell';

import EditIcon from '@mui/icons-material/Edit';
import SaveIcon from '@mui/icons-material/Save';
import CancelIcon from '@mui/icons-material/Close';

const StyledTableRow = styled(TableRow)(({ theme }) => ({
  '&:nth-of-type(odd)': {
    backgroundColor: theme.palette.action.hover,
  },
  // hide last border
  '&:last-child td, &:last-child th': {
    border: 0,
  },
}));

const useScoutViewMutation = () => {
  return useCallback(
    (member_no, answers) =>
      new Promise((resolve, reject) => {
        fetch(`/update_status?member_no=${member_no}`, {
          method: 'POST',
          body: JSON.stringify(answers),
          headers: {"content-type": "application/json"},
        })
        .then((response) => response.json())
        .then((data) => {resolve()})
        .catch((err) => reject(err))
//              reject(new Error("Error while saving user: name can't be empty."));
        }),
    [],
  );
};

function Participants() {
  const [tableData,setTableData]=useState([]);
  const [loadingParticipants,setLoadingParticipants]=useState(true);
  const [statusColumns,setStatusColumns]=useState([]);
  const [dataColumns,setDataColumns]=useState([]);
  const [snackbar, setSnackbar] = useState(null);
  const [rowModesModel, setRowModesModel] = useState({});

  const mutateRow = useScoutViewMutation()

  const handleCloseSnackbar = () => setSnackbar(null);

  const handleRowEditStart = (params, event) => {
    event.defaultMuiPrevented = true;
  };

  const handleRowEditStop = (params, event) => {
    event.defaultMuiPrevented = true;
  };

  const handleEditClick = (id) => () => {
    setRowModesModel({ ...rowModesModel, [id]: { mode: GridRowModes.Edit } });
  };

  const handleSaveClick = (id) => () => {
    setRowModesModel({ ...rowModesModel, [id]: { mode: GridRowModes.View } });
  };

  const handleCancelClick = (id) => () => {
    setRowModesModel({
      ...rowModesModel,
      [id]: { mode: GridRowModes.View, ignoreModifications: true },
    });
    setTableData(tableData);
  };

  const handleRowEditCommit = (params) => (p) => {
    console.log(params, p)
  }

  const processRowUpdate = useCallback(
    async (newRow, oldRow) => {
      // filter only status
      let statusAnswers = {};
      for (let q in statusColumns) {
        let sid = statusColumns[q].field
        //if (newRow['questions'][sid] === oldRow['questions'][sid]) {continue};
        statusAnswers[sid] = newRow['questions'][sid]
      }
      // Make the HTTP request to save in the backend
      await mutateRow(oldRow.member_no, statusAnswers);
      setSnackbar({ children: `Deltagare ${oldRow.member_no} uppdaterad`, severity: 'success' });
      setLoadingParticipants(true);
      fetch(`/participants?form=${params.form_id}&size=10000&cache=0`)
        .then((response) => response.json())
          .then((data) => {
            setTableData(data.items);
            setLoadingParticipants(false);
          });
      return newRow;
    },
    [statusColumns, mutateRow],
  );

  const handleProcessRowUpdateError = useCallback((error) => {
    setSnackbar({ children: error.message, severity: 'error' });
  }, []);

  // Get ID from URL
  const params = useParams();

  useEffect(()=>{
		fetch(`/questions?form_id=${params.form_id}`)
      .then((response) => response.json())
        .then((data) => {
          setStatusColumns(get_status_columns(data));
          setDataColumns(get_data_columns(data));
        });
    setLoadingParticipants(true);
    fetch(`/participants?form=${params.form_id}&size=10000`)
      .then((response) => response.json())
        .then((data) => {
          setTableData(data.items);
          setLoadingParticipants(false);
        });
  }, []);

  function getQuestionType(type) {
    switch (type) {
      case 'choice':
        return 'singleSelect'
      case 'number':
        return 'number'
      case 'boolean':
        return 'boolean'
        default:
        return 'string'
    }
  }

  function choiceFormater({ id: rowId, value, field, api }) {
    const colDef = api.getColumn(field);
    const option = colDef.valueOptions.find(
      ({ value: optionValue }) => value === optionValue
    );

    return option == null ? (value == null ? '' : value.label) : option.label;
  };

  function get_status_columns(questions) {
    let columns = [];
    for (let q in questions) {
      if (questions[q].status) {
        let column = {
          field: questions[q].id.toString(),
          headerName: questions[q].question,
          editable: true,
          valueGetter: (params) => {
            return params.row.questions[questions[q].id]
          },
          valueSetter: (params) => {
            let copy = {}
            Object.assign(copy, params.row);
            copy.questions[questions[q].id] = params.value
            return {...copy}
          },
          type: getQuestionType(questions[q].type),
          width: 10 * questions[q].question.length
        }
        if (questions[q].type === 'choice') {
          let options = []
          for (const [key, value] of Object.entries(questions[q]['choices'])) {
            options.push({value: key, label: value.option, default: key === questions[q]['default_value']});
          }
          column.valueOptions=options;
          column.valueFormatter=choiceFormater;
        }
        columns.push(column)
      }
    }
    return columns;
  }

  function get_data_columns(questions) {
    let columns = [];
    for (let q in questions) {
      if (!(questions[q].status)) {
        let column = {
          field: questions[q].id.toString(),
          tab: questions[q].tab_title,
          section: questions[q].section_title,
          headerName: questions[q].question,
          valueGetter: (params) => {
            let value = params?.row ? params.row.questions[questions[q].id] : params
            if (questions[q].type === 'other_unsupported_by_api' && value?.includes('linked_id'))
              return JSON.parse(value)['value']
            if (questions[q].type === 'boolean')
              return value ? 'Ja' : 'Nej'
            if (questions[q].type === 'choice' && questions[q]['choices'][value])
              return questions[q]['choices'][value]['option']
            return value
          },
          type: getQuestionType(questions[q].type),
          width: 10 * questions[q].question.length,
          hide: true
        }
        if (questions[q].type === 'choice') {
          let options = []
          for (const [key, value] of Object.entries(questions[q]['choices'])) {
            options.push({value: key, label: value.option, default: key === questions[q]['default_value']});
          }
          column.valueOptions=options;
          column.valueFormatter=choiceFormater;
        }
        columns.push(column)
      }
    }
    return columns;
  }

  const columns = [
    { field: 'member_no', headerName: 'Medlemsnummer', width: 150, type: 'string'},
    { field: 'first_name', headerName: 'Förnamn'},
    { field: 'last_name', headerName: 'Efternamn'},
    { field: 'date_of_birth', headerName: 'Födelsedatum', width: 150, type: 'date',
      width: 180, valueGetter: ({ value }) => value && new Date(value),
      valueFormatter: params => moment(params.value).format("YYYY-MM-DD")},
    { field: 'registration_date', headerName: 'Anmälningsdatum', type: 'dateTime',
      width: 180, valueGetter: ({ value }) => value && new Date(value),
      valueFormatter: params => moment(params.value).format("YYYY-MM-DD HH:mm")},
    { field: 'cancelled_date', headerName: 'Avbokningdatum', width: 150, type: 'dateTime',
      valueGetter: ({ value }) => value && new Date(value),
      valueFormatter: params => params.value && moment(params.value).format("YYYY-MM-DD HH:mm"), hide: true},
    { field: 'sex', headerName: 'Kön', width: 50, type: 'singleSelect',
      valueOptions: [{value: 0, label: 'Okänt'},{value: 1, label: 'Man'},{value: 2, label: 'Kvinna'}, {value: 3, label: 'Annat'}],
      valueFormatter: choiceFormater, hide: true},
  ];

  const actions = [
    { field: 'actions', type: 'actions', width: 100, cellClassName: 'actions',
      getActions: ({ id }) => {
        const isInEditMode = rowModesModel[id]?.mode === GridRowModes.Edit;

        if (isInEditMode) {
          return [
            <GridActionsCellItem
              icon={<SaveIcon />}
              label="Save"
              onClick={handleSaveClick(id)}
            />,
            <GridActionsCellItem
              icon={<CancelIcon />}
              label="Cancel"
              className="textPrimary"
              onClick={handleCancelClick(id)}
              color="inherit"
              />,
            ];
          }

          return [
          <GridActionsCellItem
          icon={<EditIcon />}
          label="Edit"
          className="textPrimary"
            onClick={handleEditClick(id)}
            color="inherit"
            />,
          ];
      }}]

  return (
    <Box style={{
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      height: '85vh',
      margin:40 }}
      sx={{
        '& .cancelled--true': {
          bgcolor: 'pink',
          '&:hover': {
            bgcolor: 'pink'
      }}}}>
      <DataGridPremium
        rows={tableData}
        columns={[...columns, ...statusColumns, ...dataColumns, ...actions]}
        getRowId={(row) => row.member_no}
        localeText={svSE.components.MuiDataGrid.defaultProps.localeText}
        density="compact"
        disableDensitySelector
        sx={{ fontSize: 16 }}
        getRowClassName={(params) => `cancelled--${params.row.cancelled_date != null}`}
        components={{ Toolbar: GridToolbar}}
        pagination
        loading={loadingParticipants}
        experimentalFeatures={{ newEditingApi: true }}
        isCellEditable={(params) => params.row.cancelled_date == null}
        onRowEditStart={handleRowEditStart}
        onRowEditStop={handleRowEditStop}
        rowModesModel={rowModesModel}
        onRowModesModelChange={(newModel) => setRowModesModel(newModel)}
        editMode='row'
        processRowUpdate={processRowUpdate}
        onProcessRowUpdateError={handleProcessRowUpdateError}
        onRowEditCommit={handleRowEditCommit}

        componentsProps={{
          toolbar: {
            showQuickFilter: true,
            quickFilterProps: { debounceMs: 500 },
          },
        }}
        getDetailPanelHeight={() => 'auto'}
        getDetailPanelContent= {({ row }) => (
          <Table size='small' >
            <TableBody>
            <TableRow key='h'>
              <TableCell component="th" colSpan="2"><b>Grundläggande info i scoutnet</b></TableCell>
            </TableRow>
            <StyledTableRow key='cancelled_date'>
              <TableCell component="th">Avanmäld</TableCell>
              <TableCell component="td">{row.cancelled_date}</TableCell>
            </StyledTableRow>
            <StyledTableRow key='primary_email'>
              <TableCell component="th">Epost i scoutnet</TableCell>
              <TableCell component="td">{row.primary_email}</TableCell>
            </StyledTableRow>
            <StyledTableRow key='sex'>
              <TableCell component="th">Kön</TableCell>
              <TableCell component="td">{columns.find(c => c.field === 'sex').valueOptions.find(o => o.value === row.sex).label}</TableCell>
            </StyledTableRow>
            {(() => {
              let rows = []
              let tab = ''
              let section = ''
              for (let q in dataColumns) {
                if (dataColumns[q].tab !== '' & dataColumns[q].tab !== null & tab !== dataColumns[q].tab){
                  tab = dataColumns[q].tab;
                  rows.push(
                    <StyledTableRow key={tab}>
                      <TableCell component="th" colSpan="2"><h3>{tab}</h3></TableCell>
                    </StyledTableRow>
                  )
                }
                if (dataColumns[q].section !== '' & dataColumns[q].section !== null & section !== dataColumns[q].section){
                  section = dataColumns[q].section;
                  rows.push(
                    <StyledTableRow key={section}>
                      <TableCell component="th" colSpan="2"><b>{section}</b></TableCell>
                    </StyledTableRow>
                  )
                }
                rows.push(
                  <StyledTableRow key={dataColumns[q].field}>
                    <TableCell component="th">{dataColumns[q].headerName}</TableCell>
                    <TableCell component="td">{dataColumns[q].valueGetter(row?.questions[dataColumns[q].field])}</TableCell>
                  </StyledTableRow>
                )}
              return rows;
            })()}
            </TableBody>
          </Table>
        )}
      />

      {!!snackbar && (
        <Snackbar
          open
          anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
          onClose={handleCloseSnackbar}
          autoHideDuration={6000}
        >
          <Alert {...snackbar} onClose={handleCloseSnackbar} />
        </Snackbar>
      )}
    </Box>
  );
}

export default Participants;
