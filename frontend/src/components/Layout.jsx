import { useState } from 'react'
import { Outlet } from 'react-router-dom'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import Sidebar from './Sidebar'
import Header from './Header'

export default function Layout() {
  const [collapsed, setCollapsed] = useState(true)

  return (
    <div className="flex h-screen w-full overflow-hidden bg-slate-100">
      <Sidebar collapsed={collapsed} />

      {/* Chevron toggle button — at sidebar/header junction */}
      <div style={{ position: 'relative', flexShrink: 0 }}>
        <button
          onClick={() => setCollapsed(!collapsed)}
          style={{
            position: 'absolute',
            top: '18px',
            left: '-13px',
            zIndex: 50,
            width: 26,
            height: 26,
            borderRadius: '50%',
            backgroundColor: '#ffffff',
            border: '1.5px solid #e2e8f0',
            boxShadow: '0 2px 6px rgba(0,0,0,0.12)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            color: '#505081',
            transition: 'background-color 0.15s',
          }}
          onMouseEnter={e => e.currentTarget.style.backgroundColor = '#f5f5fb'}
          onMouseLeave={e => e.currentTarget.style.backgroundColor = '#ffffff'}
        >
          {collapsed
            ? <ChevronRight size={14} strokeWidth={2.5} />
            : <ChevronLeft size={14} strokeWidth={2.5} />
          }
        </button>
      </div>

      <div className="flex flex-col flex-1 min-w-0 overflow-hidden">
        <Header />
        <main className="flex-1 overflow-y-auto" style={{ padding: '32px' }}>
          <Outlet />
        </main>
      </div>
    </div>
  )
}
