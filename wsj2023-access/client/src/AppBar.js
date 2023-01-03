import * as React from 'react';

import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Container from '@mui/material/Container';
import Toolbar from '@mui/material/Toolbar';
import Forms from './Forms.js';

import logo from './scoutlogo2.png';

const ResponsiveAppBar = () => {
  return (
    <AppBar position="static"  style={{ background : "#002c4d" }}>
      <Container maxWidth="xl">
        <Toolbar disableGutters>
          <Box sx={{display: {xs: 'flex', md: 'flex' },flex:900000, mr: 3, ml:3 }} >
            <a href='/client'><img width={170} src={logo} alt="Logo" /></a>
          </Box>

          <Box sx={{ flexGrow: 1, display: { xs: 'flex', md: 'flex' } }}>
            <React.Fragment key='right'>
              <Forms/>
            </React.Fragment>
          </Box>
        </Toolbar>
      </Container>
    </AppBar>
  );
};
export default ResponsiveAppBar;