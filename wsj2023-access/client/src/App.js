import { BrowserRouter, Routes, Route } from "react-router-dom";
import './App.css';
import AppBar from './AppBar'
import Participants from './Participants'

function App() {
  return (
    <div className="App">
      <AppBar />
      <BrowserRouter basename="/client">
        <Routes>
          <Route path="/" element={<div><p>Select Scoutnet form in right dropdown</p></div>} />
          <Route path="/:form_id" element={<Participants />} />
        </Routes>
      </BrowserRouter>
    </div>
  );
}

export default App;
