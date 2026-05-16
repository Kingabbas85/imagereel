import { Search, Bell, User } from 'lucide-react'
import { useLocation } from 'react-router-dom'

const C1 = '#272757'
const C2 = '#8686AC'
const C3 = '#505081'
const C4 = '#0F0E47'

const titles = {
  '/dashboard': 'Dashboard',
  '/my-videos': 'My Videos',
  '/projects':  'Projects',
  '/settings':  'Settings',
}

export default function Header() {
  const { pathname } = useLocation()
  const title = titles[pathname] ?? 'ReelGen'

  return (
    <header
      className="sticky top-0 z-10 flex items-center justify-between gap-4 bg-white"
      style={{ height: '60px', padding: '0 32px', borderBottom: `2px solid ${C3}` }}
    >
      <h1 style={{ fontWeight: 700, fontSize: 17, color: C4 }}>{title}</h1>

      <div className="flex items-center gap-3">
        {/* Search */}
        <div className="relative hidden sm:block">
          <Search size={15} style={{ position:'absolute', left:12, top:'50%', transform:'translateY(-50%)', color: C2 }} />
          <input
            type="text"
            placeholder="Search..."
            className="text-sm rounded-lg w-52 transition-colors"
            style={{ padding:'8px 16px 8px 36px', backgroundColor:'#f1f5f9', border:`1px solid transparent`, outline:'none' }}
            onFocus={e => e.target.style.borderColor = C3}
            onBlur={e => e.target.style.borderColor = 'transparent'}
          />
        </div>

        {/* Bell */}
        <button className="relative w-9 h-9 flex items-center justify-center rounded-lg hover:bg-slate-100 transition-colors" style={{ color: C3 }}>
          <Bell size={18} />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 rounded-full" style={{ backgroundColor: C2 }}></span>
        </button>

        {/* Avatar */}
        <button className="flex items-center gap-2 pl-1 pr-3 py-1 rounded-lg transition-colors">
          <div className="w-8 h-8 rounded-full flex items-center justify-center" style={{ backgroundColor: C1 }}>
            <User size={15} color="white" />
          </div>
          <span className="text-sm font-medium hidden sm:block" style={{ color: C4 }}>Admin</span>
        </button>
      </div>
    </header>
  )
}
