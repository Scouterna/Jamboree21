import * as React from 'react';
import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Toolbar from '@mui/material/Toolbar';
import Container from '@mui/material/Container';
import Button from '@mui/material/Button';
import CreditCardIcon from '@mui/icons-material/CreditCard';
import AccountCircleIcon from '@mui/icons-material/AccountCircle';
import CategoryIcon from '@mui/icons-material/Category';
import logo from './scoutlogo2.png'; 
import MenuIcon from '@mui/icons-material/Menu';
import LogoutIcon from '@mui/icons-material/Logout';

import Drawer from '@mui/material/Drawer';
import List from '@mui/material/List';
import Divider from '@mui/material/Divider';
import ListItem from '@mui/material/ListItem';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemIcon from '@mui/material/ListItemIcon';
import ListItemText from '@mui/material/ListItemText';

const ResponsiveAppBar = () => {
	
const [state, setState] = React.useState({
	right: false,
  });

const toggleDrawer = (anchor, open) => (event) => {
	if (event.type === 'keydown' && (event.key === 'Tab' || event.key === 'Shift')) {
	  return;
	}

	setState({ ...state, [anchor]: open });
  };
  
  
  
 function logout() {
	  //localStorage.setItem('credentials', "");
	  localStorage.removeItem("credentials");
 }

  const list = (anchor) => (
	<Box
	  sx={{ width: anchor === 'top' || anchor === 'bottom' ? 'auto' : 250 }}
	  role="presentation"
	  onClick={toggleDrawer(anchor, false)}
	  onKeyDown={toggleDrawer(anchor, false)}
	>
	  <List>
		  <ListItem disablePadding>
			<ListItemButton component='a' href="/" >
			  <ListItemIcon>
				<CreditCardIcon />
			  </ListItemIcon>
			  <ListItemText primary='Transaktioner' />
			</ListItemButton>
		  </ListItem>
		  
		  <ListItem disablePadding>
			  <ListItemButton component='a' href="/cardholders" >
				<ListItemIcon>
				  <AccountCircleIcon />
				</ListItemIcon>
				<ListItemText primary='Korthållare' />
			  </ListItemButton>
			</ListItem>
			
		<ListItem disablePadding>
		  <ListItemButton component='a' href="/categories" >
			<ListItemIcon>
			  <CategoryIcon />
			</ListItemIcon>
			<ListItemText primary='Kategorier' />
		  </ListItemButton>
		</ListItem>
		  
	  </List>
	  
	  <Divider />
	  <List>
	  <ListItem disablePadding>
		<ListItemButton component='a' href="/" onClick={logout} >
		  <ListItemIcon>
			<LogoutIcon />
		  </ListItemIcon>
		  <ListItemText primary='Logga ut' />
		</ListItemButton>
	  </ListItem>
	  </List>
	</Box>
  );

  return (
		
	  
	<AppBar position="static"  style={{ background : "#002c4d" }}>
	  <Container maxWidth="xl">
		<Toolbar disableGutters>
		
			<Box sx={{display: {xs: 'flex', md: 'flex' },flex:900000, mr: 3, ml:3 }} >
				<a href='/'><img width={170} src={logo} alt="Logo" /></a>
			</Box>
		
		 

		  <Box sx={{ flexGrow: 1, display: { xs: 'flex', md: 'flex' } }}>
			
			<React.Fragment key='right'>
				<Button sx={{color:'white',mr:2}} startIcon={<MenuIcon />} onClick={toggleDrawer('right', true)}></Button>
				<Drawer anchor='right' open={state['right']} onClose={toggleDrawer('right', false)} >
				  {list('right')}
				</Drawer>
			  </React.Fragment>
			
		  </Box>

		</Toolbar>
	  </Container>
	</AppBar>
  );
};
export default ResponsiveAppBar;
