

import './index.css';
import Wisetable from './Wisetable';
import Cardholders from './Cardholders';
import Categories from './Categories';
import Login from './Login';
import Topbar from './Topbar';
import AuthWrapper from './AuthWrapper';
import { BrowserRouter, Routes, Route } from "react-router-dom";
//import { useState,useEffect } from "react";

function App() {

  return (
	<div className="App">
	 <BrowserRouter>
	   <Routes>	   
	   <Route path="/login" element={<Login />} />
	   <Route element={<AuthWrapper />}>
	   		<Route path="/" element={<div><Topbar /><Wisetable /></div>} />
		 	<Route path="/cardholders" element={<div><Topbar /><Cardholders /></div>} />
		    <Route path="/categories" element={<div><Topbar /><Categories /></div>} />
		</Route>
		 
	   </Routes>
	 </BrowserRouter>
	</div>
  );
}

export default App;