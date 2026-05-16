import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  Video, CheckCircle2, Clock, XCircle,
  Search, Download, Trash2, Eye,
  Plus, ChevronDown, ChevronLeft, ChevronRight,
  RefreshCw, Filter,
} from 'lucide-react'

const API_BASE = 'http://127.0.0.1:8000'

const badgeStyle = { display: 'inline-flex', alignItems: 'center', padding: '4px 12px', borderRadius: 999, fontSize: 12, fontWeight: 500 }

function StatusBadge({ status }) {
  if (status === 'completed')               return <span style={{ ...badgeStyle, backgroundColor: '#d1fae5', color: '#065f46' }}>Completed</span>
  if (status === 'processing')              return <span style={{ ...badgeStyle, backgroundColor: '#dbeafe', color: '#1e40af' }}>Processing</span>
  if (status === 'queued')                  return <span style={{ ...badgeStyle, backgroundColor: '#dbeafe', color: '#1e40af' }}>Queued</span>
  if (status === 'pending' || status === 'draft') return <span style={{ ...badgeStyle, backgroundColor: '#fef3c7', color: '#92400e' }}>Draft</span>
  if (status === 'failed')                  return <span style={{ ...badgeStyle, backgroundColor: '#fee2e2', color: '#991b1b' }}>Failed</span>
  return <span style={{ ...badgeStyle, backgroundColor: '#f1f5f9', color: '#64748b' }}>{status}</span>
}

function StatCard({ icon: Icon, label, value, bg, iconColor, border }) {
  return (
    <div style={{ backgroundColor: 'white', borderRadius: 16, border: '1px solid #e2e8f0', padding: 20, display: 'flex', alignItems: 'center', gap: 16, boxShadow: '0 1px 3px rgba(0,0,0,0.06)', borderTop: `3px solid ${border}` }}>
      <div style={{ width: 48, height: 48, borderRadius: 12, backgroundColor: bg, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
        <Icon size={22} color={iconColor} />
      </div>
      <div>
        <p style={{ fontSize: 24, fontWeight: 700, color: '#1e293b', lineHeight: 1 }}>{value}</p>
        <p style={{ fontSize: 13, color: '#64748b', marginTop: 4 }}>{label}</p>
      </div>
    </div>
  )
}

export default function MyVideos() {
  const navigate = useNavigate()
  const [data, setData]           = useState([])
  const [stats, setStats]         = useState({ total: 0, completed: 0, processing: 0, failed: 0 })
  const [pagination, setPagination] = useState({ total: 0, per_page: 10, current_page: 1, last_page: 1, from: 0, to: 0 })
  const [search, setSearch]       = useState('')
  const [searchInput, setSearchInput] = useState('')
  const [filter, setFilter]       = useState('')
  const [perPage, setPerPage]     = useState(10)
  const [loading, setLoading]     = useState(true)
  const [error, setError]         = useState(null)

  const fetchData = useCallback(async (page = 1) => {
    setLoading(true)
    setError(null)
    try {
      const params = new URLSearchParams({
        page,
        per_page: perPage,
        ...(search && { search }),
        ...(filter && { status: filter }),
      })
      const res = await fetch(`${API_BASE}/api/video-projects?${params}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const json = await res.json()
      setData(json.data)
      setStats(json.stats)
      setPagination(json.pagination)
    } catch (e) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }, [search, filter, perPage])

  useEffect(() => { fetchData(1) }, [fetchData])

  const goToPage = (page) => {
    if (page < 1 || page > pagination.last_page) return
    fetchData(page)
  }

  const handleSearch = () => {
    setSearch(searchInput)
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>

      {/* Stat cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16 }}>
        <StatCard icon={Video}        label="Total Videos" value={stats.total}      bg="rgba(15,14,71,0.08)"    iconColor="#0F0E47" border="#0F0E47" />
        <StatCard icon={CheckCircle2} label="Completed"    value={stats.completed}  bg="rgba(39,39,87,0.1)"    iconColor="#272757" border="#272757" />
        <StatCard icon={Clock}        label="Processing"   value={stats.processing} bg="rgba(80,80,129,0.12)"  iconColor="#505081" border="#505081" />
        <StatCard icon={XCircle}      label="Failed"       value={stats.failed}     bg="rgba(134,134,172,0.15)" iconColor="#8686AC" border="#8686AC" />
      </div>

      {/* Table card */}
      <div style={{ backgroundColor: 'white', borderRadius: 16, border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.06)', overflow: 'hidden' }}>

        {/* Toolbar */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 12, padding: '14px 20px', borderBottom: '1px solid #f1f5f9' }}>
          <h2 style={{ fontWeight: 600, fontSize: 15, color: '#1e293b' }}>All Videos</h2>

          <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>

            {/* Search */}
            <div style={{ position: 'relative' }}>
              <Search size={14} style={{ position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8' }} />
              <input
                type="text"
                placeholder="Search videos..."
                value={searchInput}
                onChange={e => setSearchInput(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && handleSearch()}
                style={{ paddingLeft: 32, paddingRight: 12, paddingTop: 7, paddingBottom: 7, fontSize: 13, backgroundColor: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, width: 200, outline: 'none' }}
              />
            </div>

            {/* Status filter */}
            <div style={{ position: 'relative' }}>
              <Filter size={13} style={{ position: 'absolute', left: 9, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8' }} />
              <ChevronDown size={13} style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
              <select
                value={filter}
                onChange={e => setFilter(e.target.value)}
                style={{ paddingLeft: 28, paddingRight: 28, paddingTop: 7, paddingBottom: 7, fontSize: 13, backgroundColor: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, appearance: 'none', cursor: 'pointer', outline: 'none' }}
              >
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="processing">Processing</option>
                <option value="queued">Queued</option>
                <option value="draft">Draft</option>
                <option value="failed">Failed</option>
              </select>
            </div>

            {/* Per page */}
            <div style={{ position: 'relative' }}>
              <ChevronDown size={13} style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', color: '#94a3b8', pointerEvents: 'none' }} />
              <select
                value={perPage}
                onChange={e => setPerPage(Number(e.target.value))}
                style={{ paddingLeft: 10, paddingRight: 28, paddingTop: 7, paddingBottom: 7, fontSize: 13, backgroundColor: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, appearance: 'none', cursor: 'pointer', outline: 'none' }}
              >
                <option value={10}>10 / page</option>
                <option value={25}>25 / page</option>
                <option value={50}>50 / page</option>
              </select>
            </div>

            {/* Refresh */}
            <button
              onClick={() => fetchData(pagination.current_page)}
              style={{ width: 34, height: 34, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: 8, border: '1px solid #e2e8f0', backgroundColor: '#f8fafc', cursor: 'pointer', color: '#64748b' }}
              title="Refresh"
            >
              <RefreshCw size={14} className={loading ? 'animate-spin' : ''} />
            </button>

            {/* New Video */}
            <button
              onClick={() => navigate('/my-videos/new')}
              style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 14px', backgroundColor: '#505081', color: 'white', fontSize: 13, fontWeight: 500, borderRadius: 8, border: 'none', cursor: 'pointer' }}
              onMouseEnter={e => e.currentTarget.style.backgroundColor = '#272757'}
              onMouseLeave={e => e.currentTarget.style.backgroundColor = '#505081'}
            >
              <Plus size={14} /> New Video
            </button>
          </div>
        </div>

        {/* Table */}
        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', fontSize: 13, borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid #f1f5f9', backgroundColor: '#fafafa' }}>
                {['#', 'Title', 'Status', 'Resolution', 'Duration', 'Size', 'Created', 'Actions'].map(h => (
                  <th key={h} style={{ textAlign: h === 'Actions' ? 'right' : 'left', padding: '10px 16px', fontSize: 11, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.06em', fontWeight: 600, whiteSpace: 'nowrap' }}>
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {error && (
                <tr><td colSpan={8} style={{ textAlign: 'center', padding: '48px 20px', color: '#ef4444' }}>
                  Error: {error} — Laravel server running hai?
                </td></tr>
              )}
              {!error && loading && (
                <tr><td colSpan={8} style={{ textAlign: 'center', padding: '48px 20px', color: '#94a3b8' }}>
                  <RefreshCw size={20} style={{ margin: '0 auto 8px', display: 'block' }} className="animate-spin" />
                  Loading...
                </td></tr>
              )}
              {!error && !loading && data.length === 0 && (
                <tr><td colSpan={8} style={{ textAlign: 'center', padding: '48px 20px', color: '#94a3b8' }}>
                  <Video size={32} style={{ margin: '0 auto 10px', display: 'block', color: '#cbd5e1' }} />
                  No videos found
                </td></tr>
              )}
              {!error && !loading && data.map((video, i) => (
                <tr key={video.id}
                  style={{ borderBottom: '1px solid #f8fafc', backgroundColor: i % 2 !== 0 ? '#fafafa' : 'white', transition: 'background-color 0.1s' }}
                  onMouseEnter={e => e.currentTarget.style.backgroundColor = '#f1f5f9'}
                  onMouseLeave={e => e.currentTarget.style.backgroundColor = i % 2 !== 0 ? '#fafafa' : 'white'}
                >
                  <td style={{ padding: '12px 16px', color: '#94a3b8', fontSize: 12 }}>{pagination.from + i}</td>
                  <td style={{ padding: '12px 16px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                      <div style={{ width: 34, height: 34, borderRadius: 8, backgroundColor: 'rgba(80,80,129,0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                        <Video size={15} color="#505081" />
                      </div>
                      <span style={{ fontWeight: 500, color: '#334155' }}>{video.title}</span>
                    </div>
                  </td>
                  <td style={{ padding: '12px 16px' }}><StatusBadge status={video.status} /></td>
                  <td style={{ padding: '12px 16px', color: '#64748b', fontFamily: 'monospace', fontSize: 12 }}>{video.resolution ?? '—'}</td>
                  <td style={{ padding: '12px 16px', color: '#64748b' }}>{video.duration}</td>
                  <td style={{ padding: '12px 16px', color: '#64748b' }}>{video.size}</td>
                  <td style={{ padding: '12px 16px', color: '#94a3b8', fontSize: 12 }}>{video.created_at}</td>
                  <td style={{ padding: '12px 16px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 2 }}>
                      <button title="Preview" disabled={!video.has_video} className="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><Eye size={14} /></button>
                      <button title="Download" disabled={!video.has_video} className="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><Download size={14} /></button>
                      <button title="Delete" className="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors"><Trash2 size={14} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination footer */}
        {!loading && pagination.total > 0 && (
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', borderTop: '1px solid #f1f5f9', flexWrap: 'wrap', gap: 8 }}>
            <span style={{ fontSize: 12, color: '#94a3b8' }}>
              Showing {pagination.from}–{pagination.to} of {pagination.total} videos
            </span>
            <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
              <button onClick={() => goToPage(pagination.current_page - 1)} disabled={pagination.current_page === 1}
                style={{ width: 32, height: 32, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: 6, border: '1px solid #e2e8f0', backgroundColor: 'white', cursor: 'pointer', color: '#64748b', opacity: pagination.current_page === 1 ? 0.4 : 1 }}>
                <ChevronLeft size={14} />
              </button>

              {Array.from({ length: pagination.last_page }, (_, i) => i + 1)
                .filter(p => p === 1 || p === pagination.last_page || Math.abs(p - pagination.current_page) <= 1)
                .reduce((acc, p, idx, arr) => {
                  if (idx > 0 && p - arr[idx - 1] > 1) acc.push('...')
                  acc.push(p)
                  return acc
                }, [])
                .map((p, idx) => p === '...'
                  ? <span key={`e${idx}`} style={{ padding: '0 4px', color: '#94a3b8', fontSize: 13 }}>…</span>
                  : <button key={p} onClick={() => goToPage(p)}
                      style={{ width: 32, height: 32, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: 6, border: '1px solid', fontSize: 13, cursor: 'pointer', fontWeight: p === pagination.current_page ? 600 : 400, borderColor: p === pagination.current_page ? '#505081' : '#e2e8f0', backgroundColor: p === pagination.current_page ? '#505081' : 'white', color: p === pagination.current_page ? 'white' : '#64748b' }}>
                      {p}
                    </button>
                )
              }

              <button onClick={() => goToPage(pagination.current_page + 1)} disabled={pagination.current_page === pagination.last_page}
                style={{ width: 32, height: 32, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: 6, border: '1px solid #e2e8f0', backgroundColor: 'white', cursor: 'pointer', color: '#64748b', opacity: pagination.current_page === pagination.last_page ? 0.4 : 1 }}>
                <ChevronRight size={14} />
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
