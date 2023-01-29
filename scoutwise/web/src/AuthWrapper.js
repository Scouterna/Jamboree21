import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useState,useEffect } from "react";

const AuthWrapper = () => {
  const location = useLocation(); // current location

  const [loginok,setLoginok]=useState();

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

	const authorize = async () => {
	try {



		fetch("/api/validatekey", { headers:heads})
		.then(
			function(response) {
				if (response.status === 200) { setLoginok(true); return; }
				else { setLoginok(false);  }
			}
		)
	}
	catch (e) {setLoginok(false);  }

	};

	authorize();

	}, []);


	//if (!isTokenValidated) return <div />;
	if (loginok === undefined) {
		return <div>Laddar...</div>; // or loading indicator/spinner/etc
  	}

  return loginok
  ? <Outlet />
  : <Navigate to="/Login" replace state={{ from: location }} />;
};

export default AuthWrapper;