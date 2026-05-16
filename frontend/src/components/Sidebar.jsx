import { NavLink } from 'react-router-dom'
import { LayoutDashboard, Video, FolderOpen, Settings, Clapperboard, LogOut } from 'lucide-react'

const navItems = [
  { to: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
  { to: '/my-videos',  icon: Video,           label: 'My Videos' },
  { to: '/projects',   icon: FolderOpen,      label: 'Projects' },
  { to: '/settings',   icon: Settings,        label: 'Settings' },
]

const C1 = '#272757'
const C3 = '#505081'
const C4 = '#0F0E47'

export default function Sidebar({ collapsed }) {
  return (
    <aside
      className="sidebar-transition flex flex-col h-screen sticky top-0 shrink-0 select-none overflow-hidden"
      style={{ width: collapsed ? 68 : 280, backgroundColor: '#f5f5fb', boxShadow: '4px 0 12px rgba(0,0,0,0.06)' }}
    >
      {/* Brand */}
      <div style={{
        height: 60, minHeight: 60, display: 'flex', alignItems: 'center',
        padding: collapsed ? '0 14px' : '0 20px',
        backgroundColor: C1,
        gap: 12,
      }}>
        <div style={{
          width: 36, height: 36, borderRadius: 10,
          backgroundColor: C3,
          display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
        }}>
          <Clapperboard size={18} color="white" />
        </div>
        {!collapsed && (
          <span style={{ fontWeight: 700, fontSize: 15, color: '#ffffff', whiteSpace: 'nowrap', overflow: 'hidden' }}>
            ReelGen
          </span>
        )}
      </div>

      {/* Nav */}
      <nav style={{ flex: 1, padding: '10px 10px', overflowY: 'auto', overflowX: 'hidden' }}>
        {navItems.map(({ to, icon: Icon, label }) => (
          <NavLink key={to} to={to} title={collapsed ? label : undefined} style={{ textDecoration: 'none' }}>
            {({ isActive }) => (
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: collapsed ? 0 : 10,
                  justifyContent: collapsed ? 'center' : 'flex-start',
                  padding: collapsed ? '13px 0' : '13px 14px',
                  borderRadius: 8,
                  margin: '2px 0',
                  cursor: 'pointer',
                  backgroundColor: isActive ? 'rgba(134,134,172,0.18)' : 'transparent',
                  borderRight: isActive ? `3px solid ${C4}` : '3px solid transparent',
                  transition: 'background-color 0.15s',
                }}
                onMouseEnter={e => { if (!isActive) e.currentTarget.style.backgroundColor = '#ebebf5' }}
                onMouseLeave={e => { if (!isActive) e.currentTarget.style.backgroundColor = 'transparent' }}
              >
                <Icon
                  size={19}
                  color={isActive ? C3 : '#94a3b8'}
                  strokeWidth={isActive ? 2.2 : 1.8}
                  style={{ flexShrink: 0 }}
                />
                {!collapsed && (
                  <span style={{
                    fontSize: 14,
                    fontWeight: isActive ? 600 : 500,
                    color: isActive ? C1 : '#64748b',
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                  }}>
                    {label}
                  </span>
                )}
              </div>
            )}
          </NavLink>
        ))}
      </nav>

      {/* Sign out */}
      <div style={{ padding: '8px 10px 12px' }}>
        <button
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: collapsed ? 'center' : 'flex-start',
            gap: 10,
            width: '100%',
            padding: collapsed ? '12px 0' : '12px 14px',
            borderRadius: 8,
            border: 'none',
            backgroundColor: '#FEE9E7',
            cursor: 'pointer',
            transition: 'background-color 0.15s',
          }}
          onMouseEnter={e => e.currentTarget.style.backgroundColor = '#fdd8d5'}
          onMouseLeave={e => e.currentTarget.style.backgroundColor = '#FEE9E7'}
          title={collapsed ? 'Sign out' : undefined}
        >
          <LogOut size={17} color="#c0392b" strokeWidth={2} style={{ flexShrink: 0 }} />
          {!collapsed && (
            <span style={{ fontSize: 14, fontWeight: 500, color: '#c0392b' }}>Sign out</span>
          )}
        </button>
      </div>
    </aside>
  )
}
