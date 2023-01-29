import './Login.css';
import * as React from "react";
import { useNavigate } from "react-router-dom";
//import { useState,useEffect } from "react";
import { useForm } from "react-hook-form";



function Login() {

	let navigate = useNavigate();

	const { register, handleSubmit } = useForm();
	const onSubmit = async (data) => {
		fetch('/api/login', {
			method: 'POST',
			body: JSON.stringify(data)
		  })
		  .then(function(response) {
			  if (response.status === 201) { return response.json() }
			  else {alert("Fel lösenord");}
		  })
		  .then(function(response) {
			 localStorage.setItem('credentials', JSON.stringify(response));
			 navigate("/");
		  })
	}


/*
const [api_key, setApi_key] = useState(
	JSON.parse(localStorage.getItem("userLogged"))
  );



  const logIn = () => setApi_key(true);
  const logOut = () => setApi_key(false);


*/
	  return (
		<div class='backdrop'>
		<div class="login-page">
		<div class="form">
		<form id='login-form' onSubmit={handleSubmit(onSubmit)}>
			<input type='text' placeholder="Användarnamn" {...register("user")} />
			<input type='password' placeholder="Lösenord" {...register("pass")} />
			<button type="submit" form="login-form">Logga In</button>
		</form>
	   </div></div></div>
	  );
}



export default Login;