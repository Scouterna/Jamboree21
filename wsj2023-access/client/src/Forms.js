import './App.css';
import * as React from "react";
import { useState,useEffect } from "react";

import Box from '@mui/material/Box';
import Button from '@mui/material/Button';


function Forms() {
  const [forms, setForms]=useState([]);

  useEffect(()=>{
		fetch("/forms")
			.then((response) => response.json())
			.then((data) => setForms(data))
	}, []);

  return (
    <Box>
      {Object.entries(forms)
        .map( ([key, value]) =>
          <Button variant="contained" key={key} href={"/client/"+key} >{value}</Button>
      )}
    </Box>
  )
}


export default Forms;