import './App.css';
import * as React from "react";
import { useState,useEffect } from "react";

import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemText from '@mui/material/ListItemText';

function Forms() {
  const [forms, setForms]=useState([]);

  useEffect(()=>{
		fetch("/forms")
			.then((response) => response.json())
			.then((data) => setForms(data))
	}, []);

  return (
    <List>
      {Object.entries(forms)
        .map( ([key, value]) =>
          <ListItem disablePadding key={key}>
            <ListItemButton component='a' href={"/client/"+key} >
              <ListItemText primary={value} />
            </ListItemButton>
          </ListItem>)}
    </List>
  )
}



export default Forms;