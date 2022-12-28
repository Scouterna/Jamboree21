import * as React from 'react';

import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Container from '@mui/material/Container';
import Drawer from '@mui/material/Drawer';
import MenuIcon from '@mui/icons-material/Menu';
import Toolbar from '@mui/material/Toolbar';
import Forms from './FormsList.js';

import logo from './scoutlogo2.png';

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

  const list = (anchor) => (
    <Box
      sx={{ width: anchor === 'top' || anchor === 'bottom' ? 'auto' : 250 }}
      role="presentation"
      onClick={toggleDrawer(anchor, false)}
      onKeyDown={toggleDrawer(anchor, false)}
    >
      <Forms />
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