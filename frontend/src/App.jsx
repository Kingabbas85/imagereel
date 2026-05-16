import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import MyVideos from './pages/MyVideos'
import Dashboard from './pages/Dashboard'
import CreateVideo from './pages/CreateVideo'

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Layout />}>
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<Dashboard />} />
        <Route path="my-videos" element={<MyVideos />} />
        <Route path="my-videos/new" element={<CreateVideo />} />
      </Route>
    </Routes>
  )
}
