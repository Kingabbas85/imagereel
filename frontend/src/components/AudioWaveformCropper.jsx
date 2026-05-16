import { useEffect, useRef, useState } from 'react'
import WaveSurfer from 'wavesurfer.js'
import RegionsPlugin from 'wavesurfer.js/dist/plugins/regions.esm.js'
import { Play, Pause, Scissors, Clock } from 'lucide-react'

const C1 = '#272757'
const C3 = '#505081'
const BORDER = '#e2e8f0'

function fmt(s) {
  if (!s && s !== 0) return '0:00'
  const m = Math.floor(s / 60)
  const sec = Math.floor(s % 60)
  return `${m}:${sec.toString().padStart(2, '0')}`
}

export default function AudioWaveformCropper({ audioFile, onRegionChange, onDurationReady }) {
  const containerRef = useRef(null)
  const wsRef        = useRef(null)
  const regionRef    = useRef(null)

  const [isPlaying,  setIsPlaying]  = useState(false)
  const [duration,   setDuration]   = useState(0)
  const [currentTime, setCurrentTime] = useState(0)
  const [sel,        setSel]        = useState({ start: 0, end: 0 })
  const [loading,    setLoading]    = useState(true)

  useEffect(() => {
    if (!audioFile || !containerRef.current) return

    // destroy previous instance
    if (wsRef.current) { wsRef.current.destroy(); wsRef.current = null }

    const blobUrl = URL.createObjectURL(audioFile)
    setLoading(true)
    setIsPlaying(false)
    setCurrentTime(0)
    setDuration(0)

    const ws = WaveSurfer.create({
      container: containerRef.current,
      waveColor: '#8686AC',
      progressColor: C3,
      cursorColor: '#ffffff',
      cursorWidth: 2,
      barWidth: 2,
      barGap: 1,
      barRadius: 3,
      height: 88,
      normalize: true,
      interact: true,
    })

    const wsRegions = ws.registerPlugin(RegionsPlugin.create())
    wsRef.current = ws

    ws.load(blobUrl)

    ws.on('ready', (dur) => {
      setDuration(dur)
      setLoading(false)
      onDurationReady?.(dur)

      // initial region = full audio
      const r = wsRegions.addRegion({
        start: 0,
        end: dur,
        color: 'rgba(80,80,129,0.22)',
        drag: true,
        resize: true,
      })
      regionRef.current = r
      setSel({ start: 0, end: dur })
      onRegionChange?.(0, dur)
    })

    ws.on('timeupdate', (t) => setCurrentTime(t))
    ws.on('play',   () => setIsPlaying(true))
    ws.on('pause',  () => setIsPlaying(false))
    ws.on('finish', () => setIsPlaying(false))

    wsRegions.on('region-updated', (r) => {
      const s = +r.start.toFixed(1)
      const e = +r.end.toFixed(1)
      regionRef.current = r
      setSel({ start: s, end: e })
      onRegionChange?.(s, e)
    })

    return () => {
      ws.destroy()
      URL.revokeObjectURL(blobUrl)
    }
  }, [audioFile]) // eslint-disable-line react-hooks/exhaustive-deps

  const togglePlay = () => wsRef.current?.playPause()

  const playRegion = () => {
    const r = regionRef.current
    if (!r || !wsRef.current) return
    wsRef.current.setTime(r.start)
    wsRef.current.play()
    // stop at region end
    const check = wsRef.current.on('timeupdate', (t) => {
      if (t >= r.end) { wsRef.current.pause(); check() }
    })
  }

  const selectedDur = +(sel.end - sel.start).toFixed(1)

  return (
    <div style={{ marginTop: 14, borderRadius: 12, border: `1px solid ${BORDER}`, overflow: 'hidden', backgroundColor: '#0f0e47' }}>

      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', borderBottom: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <Scissors size={14} color="#8686AC" />
          <span style={{ fontSize: 12, fontWeight: 600, color: '#8686AC', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
            Audio Waveform — Drag region to crop
          </span>
        </div>
        {!loading && (
          <span style={{ fontSize: 11, color: '#6b7280' }}>
            Total: {fmt(duration)}
          </span>
        )}
      </div>

      {/* Waveform */}
      <div style={{ padding: '12px 14px', position: 'relative' }}>
        {loading && (
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 88, gap: 8, color: '#6b7280', fontSize: 12 }}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ animation: 'spin 1s linear infinite' }}>
              <path d="M21 12a9 9 0 1 1-6.219-8.56" />
            </svg>
            Loading waveform…
          </div>
        )}
        <div ref={containerRef} style={{ opacity: loading ? 0 : 1, transition: 'opacity 0.3s' }} />
      </div>

      {/* Controls */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 14px', borderTop: '1px solid rgba(255,255,255,0.08)', backgroundColor: 'rgba(0,0,0,0.25)', flexWrap: 'wrap' }}>

        {/* Play buttons */}
        <div style={{ display: 'flex', gap: 6 }}>
          <button onClick={togglePlay} disabled={loading}
            style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '6px 12px', borderRadius: 7, border: 'none', backgroundColor: C3, color: 'white', fontSize: 12, fontWeight: 600, cursor: loading ? 'not-allowed' : 'pointer', opacity: loading ? 0.5 : 1 }}>
            {isPlaying ? <Pause size={13} /> : <Play size={13} />}
            {isPlaying ? 'Pause' : 'Play All'}
          </button>
          <button onClick={playRegion} disabled={loading}
            style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '6px 12px', borderRadius: 7, border: '1px solid rgba(134,134,172,0.4)', backgroundColor: 'transparent', color: '#8686AC', fontSize: 12, fontWeight: 600, cursor: loading ? 'not-allowed' : 'pointer', opacity: loading ? 0.5 : 1 }}>
            <Play size={13} />
            Play Selection
          </button>
        </div>

        {/* Separator */}
        <div style={{ width: 1, height: 28, backgroundColor: 'rgba(255,255,255,0.1)', flexShrink: 0 }} />

        {/* Time display */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
          <Clock size={13} color="#8686AC" />
          <span style={{ fontSize: 12, color: '#9ca3af' }}>Selection:</span>
          <span style={{ fontSize: 13, fontWeight: 700, color: '#ffffff', fontVariantNumeric: 'tabular-nums' }}>
            {fmt(sel.start)}
          </span>
          <span style={{ fontSize: 12, color: '#6b7280' }}>→</span>
          <span style={{ fontSize: 13, fontWeight: 700, color: '#ffffff', fontVariantNumeric: 'tabular-nums' }}>
            {fmt(sel.end)}
          </span>
          <span style={{ marginLeft: 4, padding: '2px 8px', borderRadius: 20, backgroundColor: 'rgba(80,80,129,0.5)', fontSize: 11, fontWeight: 700, color: '#c4b5fd' }}>
            {selectedDur}s
          </span>
        </div>

        {/* Current time */}
        <div style={{ marginLeft: 'auto', fontSize: 11, color: '#6b7280', fontVariantNumeric: 'tabular-nums' }}>
          {fmt(currentTime)} / {fmt(duration)}
        </div>
      </div>

      {/* Hint */}
      <div style={{ padding: '7px 14px', borderTop: '1px solid rgba(255,255,255,0.05)', backgroundColor: 'rgba(0,0,0,0.15)' }}>
        <p style={{ fontSize: 10, color: '#4b5563', lineHeight: 1.5 }}>
          Drag the <strong style={{ color: '#8686AC' }}>purple region</strong> to move · Drag <strong style={{ color: '#8686AC' }}>edges</strong> to resize · Selected range → Trim Start &amp; End auto-filled
        </p>
      </div>

      <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
    </div>
  )
}
