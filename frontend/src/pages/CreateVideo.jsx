import { useState, useCallback, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  FileText, ImageIcon, Music2, PlayCircle, AlignLeft,
  Bookmark, ChevronDown, ChevronUp, Upload, ArrowLeft,
  Sparkles, Save, Loader2, Mic, CheckCircle2, AlertCircle,
} from 'lucide-react'
import ImageCropPanel from '../components/ImageCropPanel'
import AudioWaveformCropper from '../components/AudioWaveformCropper'

const C1 = '#272757'
const C3 = '#505081'
const BORDER = '#e2e8f0'
const LABEL_COLOR = '#374151'
const HELPER_COLOR = '#9ca3af'
const INPUT_BG = '#f8fafc'

// ── Atoms ─────────────────────────────────────────────────────────
function FieldLabel({ children, required, helper }) {
  return (
    <div style={{ marginBottom: 6 }}>
      <label style={{ fontSize: 12, fontWeight: 600, color: LABEL_COLOR, letterSpacing: '0.02em', textTransform: 'uppercase' }}>
        {children}
        {required && <span style={{ color: '#ef4444', marginLeft: 3 }}>*</span>}
      </label>
      {helper && <p style={{ fontSize: 11, color: HELPER_COLOR, marginTop: 2, textTransform: 'none', letterSpacing: 0 }}>{helper}</p>}
    </div>
  )
}

const inputBase = {
  width: '100%', boxSizing: 'border-box',
  padding: '9px 12px', fontSize: 13,
  border: `1px solid ${BORDER}`, borderRadius: 8,
  backgroundColor: INPUT_BG, color: '#1e293b',
  outline: 'none', transition: 'border-color 0.15s, box-shadow 0.15s',
  fontFamily: 'inherit',
}

function Input({ style, ...props }) {
  return (
    <input {...props}
      style={{ ...inputBase, ...style }}
      onFocus={e => { e.target.style.borderColor = C3; e.target.style.boxShadow = `0 0 0 3px rgba(80,80,129,0.1)` }}
      onBlur={e => { e.target.style.borderColor = BORDER; e.target.style.boxShadow = 'none' }}
    />
  )
}

function Textarea({ style, ...props }) {
  return (
    <textarea {...props}
      style={{ ...inputBase, resize: 'vertical', lineHeight: 1.6, ...style }}
      onFocus={e => { e.target.style.borderColor = C3; e.target.style.boxShadow = `0 0 0 3px rgba(80,80,129,0.1)` }}
      onBlur={e => { e.target.style.borderColor = BORDER; e.target.style.boxShadow = 'none' }}
    />
  )
}

function Select({ children, style, ...props }) {
  return (
    <div style={{ position: 'relative' }}>
      <select {...props}
        style={{ ...inputBase, paddingRight: 32, appearance: 'none', cursor: 'pointer', ...style }}
        onFocus={e => { e.target.style.borderColor = C3; e.target.style.boxShadow = `0 0 0 3px rgba(80,80,129,0.1)` }}
        onBlur={e => { e.target.style.borderColor = BORDER; e.target.style.boxShadow = 'none' }}
      >{children}</select>
      <ChevronDown size={13} style={{ position: 'absolute', right: 10, top: '50%', transform: 'translateY(-50%)', color: HELPER_COLOR, pointerEvents: 'none' }} />
    </div>
  )
}

function Toggle({ checked, onChange, label, helper }) {
  return (
    <div style={{ display: 'flex', alignItems: 'flex-start', gap: 12, padding: '12px 14px', backgroundColor: checked ? 'rgba(80,80,129,0.06)' : '#f8fafc', borderRadius: 10, border: `1px solid ${checked ? 'rgba(80,80,129,0.2)' : BORDER}`, transition: 'all 0.2s' }}>
      <button type="button" onClick={() => onChange(!checked)}
        style={{ flexShrink: 0, width: 40, height: 22, borderRadius: 11, border: 'none', cursor: 'pointer', backgroundColor: checked ? C3 : '#d1d5db', position: 'relative', transition: 'background-color 0.2s', marginTop: 1 }}>
        <span style={{ position: 'absolute', top: 2, left: checked ? 20 : 2, width: 18, height: 18, borderRadius: '50%', backgroundColor: 'white', transition: 'left 0.2s', boxShadow: '0 1px 4px rgba(0,0,0,0.25)' }} />
      </button>
      <div>
        <p style={{ fontSize: 13, fontWeight: 500, color: checked ? C1 : '#374151' }}>{label}</p>
        {helper && <p style={{ fontSize: 11, color: HELPER_COLOR, marginTop: 2 }}>{helper}</p>}
      </div>
    </div>
  )
}

function DropZone({ accept, multiple, helper, onChange, files, aspect }) {
  const [dragging, setDragging] = useState(false)
  return (
    <div>
      <label
        style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 8, padding: '32px 24px', border: `1.5px dashed ${dragging ? C3 : BORDER}`, borderRadius: 10, cursor: 'pointer', backgroundColor: dragging ? 'rgba(80,80,129,0.04)' : INPUT_BG, textAlign: 'center', transition: 'all 0.15s' }}
        onDragOver={e => { e.preventDefault(); setDragging(true) }}
        onDragLeave={() => setDragging(false)}
        onDrop={e => { e.preventDefault(); setDragging(false); onChange(e.dataTransfer.files) }}
      >
        <div style={{ width: 40, height: 40, borderRadius: 10, backgroundColor: 'rgba(80,80,129,0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
          <Upload size={18} color={C3} />
        </div>
        <div>
          <p style={{ fontSize: 13, color: '#4b5563' }}>Drop files here or <span style={{ color: C3, fontWeight: 600 }}>browse</span></p>
          {helper && <p style={{ fontSize: 11, color: HELPER_COLOR, marginTop: 3 }}>{helper}</p>}
        </div>
        <input type="file" accept={accept} multiple={multiple} style={{ display: 'none' }} onChange={e => onChange(e.target.files)} />
      </label>

      {/* Image previews */}
      {files && files.length > 0 && aspect && (
        <div style={{ marginTop: 10, display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(72px, 1fr))', gap: 6 }}>
          {Array.from(files).map((f, i) => (
            <div key={i} style={{ aspectRatio: aspect, borderRadius: 8, overflow: 'hidden', border: `1px solid ${BORDER}`, backgroundColor: '#f1f5f9' }}>
              <img src={URL.createObjectURL(f)} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
            </div>
          ))}
        </div>
      )}

      {/* Audio file name */}
      {files && files.length > 0 && !aspect && (
        <div style={{ marginTop: 8, padding: '8px 12px', backgroundColor: 'rgba(80,80,129,0.07)', borderRadius: 8, fontSize: 12, color: C1, fontWeight: 500 }}>
          🎵 {files[0].name}
        </div>
      )}
    </div>
  )
}

// ── Section card ─────────────────────────────────────────────────
function Section({ icon: Icon, title, description, children, collapsible, defaultOpen = true, accent }) {
  const [open, setOpen] = useState(defaultOpen)
  return (
    <div style={{ backgroundColor: 'white', borderRadius: 14, border: `1px solid ${BORDER}`, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,0.05)' }}>
      {/* Header */}
      <div
        onClick={() => collapsible && setOpen(o => !o)}
        style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 20px', backgroundColor: 'white', cursor: collapsible ? 'pointer' : 'default', borderBottom: open ? `1px solid ${BORDER}` : 'none', userSelect: 'none' }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <div style={{ width: 36, height: 36, borderRadius: 9, backgroundColor: accent ?? 'rgba(80,80,129,0.08)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
            <Icon size={17} color={C3} />
          </div>
          <div>
            <p style={{ fontSize: 14, fontWeight: 700, color: C1, lineHeight: 1.2 }}>{title}</p>
            {description && <p style={{ fontSize: 11, color: HELPER_COLOR, marginTop: 2 }}>{description}</p>}
          </div>
        </div>
        {collapsible && (
          <div style={{ color: HELPER_COLOR }}>{open ? <ChevronUp size={16} /> : <ChevronDown size={16} />}</div>
        )}
      </div>
      {open && <div style={{ padding: '18px 20px' }}>{children}</div>}
    </div>
  )
}

function Divider() {
  return <div style={{ height: 1, backgroundColor: '#f1f5f9', margin: '14px 0' }} />
}

// ── Page ─────────────────────────────────────────────────────────
export default function CreateVideo() {
  const navigate = useNavigate()
  const [saving,       setSaving]       = useState(false)
  const [transcribing, setTranscribing] = useState(false)
  const [transcribeErr, setTranscribeErr] = useState('')

  const [form, setForm] = useState({
    title: '', description: '',
    images: null,
    croppedImages: {},
    use_tts: false, audio: null, trim_start: '', trim_end: '', transcript: '',
    tts_text: '', tts_voice: 'alloy',
    animation_type: 'ken_burns', image_duration: 0, video_resolution: '576x1024',
    generate_subtitles: false, subtitle_language: 'ur',
    watermark_text: '', show_end_card: false,
  })

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }))

  // Read audio duration directly from file — no WaveSurfer dependency
  const getAudioDuration = (file) => new Promise((resolve) => {
    const audio = document.createElement('audio')
    audio.onloadedmetadata = () => { resolve(audio.duration); URL.revokeObjectURL(audio.src) }
    audio.src = URL.createObjectURL(file)
  })

  const handleAudioChange = async (files) => {
    const file = files[0]
    if (!file) return
    const dur = await getAudioDuration(file)
    setForm(f => {
      const count = f.images ? f.images.length : 0
      const perImage = count > 0 ? Math.max(3, Math.round(dur / count)) : Math.round(dur)
      return { ...f, audio: file, trim_start: 0, trim_end: +dur.toFixed(1), image_duration: perImage }
    })
  }

  const handleTranscribe = async () => {
    if (!form.audio) return
    setTranscribing(true)
    setTranscribeErr('')
    try {
      const fd = new FormData()
      fd.append('audio', form.audio)
      fd.append('language', 'ur')
      const res = await fetch('http://127.0.0.1:8000/api/transcribe', { method: 'POST', body: fd })
      const data = await res.json()
      if (!res.ok) throw new Error(data.error ?? 'Transcription failed')
      set('transcript', data.text)
    } catch (err) {
      setTranscribeErr(err.message)
    } finally {
      setTranscribing(false)
    }
  }

  // Auto-calculate Seconds Per Image from audio selection + image count
  const calcImageDuration = useCallback((trimStart, trimEnd, imageFiles) => {
    if (trimEnd <= trimStart) return
    const selected = trimEnd - trimStart
    const count = imageFiles ? imageFiles.length : 0
    const perImage = count > 0 ? Math.max(3, Math.round(selected / count)) : Math.round(selected)
    setForm(f => ({ ...f, image_duration: perImage }))
  }, [])

  const handleDurationReady = useCallback((dur) => {
    setForm(f => {
      const count = f.images ? f.images.length : 0
      const perImage = count > 0 ? Math.max(3, Math.round(dur / count)) : Math.round(dur)
      return { ...f, trim_start: 0, trim_end: +dur.toFixed(1), image_duration: perImage }
    })
  }, [])

  const handleRegionChange = useCallback((start, end) => {
    setForm(f => {
      const count = f.images ? f.images.length : 0
      const selected = end - start
      const perImage = count > 0 ? Math.max(3, Math.round(selected / count)) : Math.round(selected)
      return { ...f, trim_start: start, trim_end: end, image_duration: perImage }
    })
  }, [])

  // When image count changes while audio is loaded, recalculate
  useEffect(() => {
    if (form.trim_end > 0) {
      calcImageDuration(form.trim_start, form.trim_end, form.images)
    }
  }, [form.images?.length]) // eslint-disable-line react-hooks/exhaustive-deps

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setTimeout(() => setSaving(false), 2000) // placeholder
  }

  return (
    <div style={{ maxWidth: 1060, margin: '0 auto' }}>

      {/* ── Page header ── */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 28 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
          <button onClick={() => navigate('/my-videos')}
            style={{ width: 36, height: 36, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: 9, border: `1px solid ${BORDER}`, backgroundColor: 'white', cursor: 'pointer', color: '#64748b', boxShadow: '0 1px 2px rgba(0,0,0,0.05)' }}>
            <ArrowLeft size={16} />
          </button>
          <div>
            <h1 style={{ fontSize: 20, fontWeight: 800, color: C1, lineHeight: 1.2 }}>Create Video Project</h1>
            <p style={{ fontSize: 12, color: HELPER_COLOR, marginTop: 3 }}>Fill in the details below to generate your reel</p>
          </div>
        </div>

        <div style={{ display: 'flex', gap: 10 }}>
          <button type="button" onClick={() => navigate('/my-videos')}
            style={{ padding: '9px 18px', fontSize: 13, fontWeight: 500, borderRadius: 9, border: `1px solid ${BORDER}`, backgroundColor: 'white', cursor: 'pointer', color: '#64748b' }}>
            Cancel
          </button>
          <button type="button" onClick={handleSubmit} disabled={saving}
            style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '9px 22px', backgroundColor: C3, color: 'white', fontSize: 13, fontWeight: 600, borderRadius: 9, border: 'none', cursor: saving ? 'not-allowed' : 'pointer', opacity: saving ? 0.7 : 1, boxShadow: '0 2px 8px rgba(80,80,129,0.35)' }}
            onMouseEnter={e => { if (!saving) e.currentTarget.style.backgroundColor = C1 }}
            onMouseLeave={e => { if (!saving) e.currentTarget.style.backgroundColor = C3 }}
          >
            {saving ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
            {saving ? 'Saving…' : 'Save & Generate'}
          </button>
        </div>
      </div>

      {/* ── Two-column grid ── */}
      <form onSubmit={handleSubmit}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>

          {/* LEFT */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>

            {/* Project Info */}
            <Section icon={FileText} title="Project Information" description="Name your project and add optional notes.">
              <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                <div>
                  <FieldLabel required helper="A short, descriptive name for this project">Project Title</FieldLabel>
                  <Input placeholder="e.g. Muharram Naat 2025, Iqbal Ki Shayari" value={form.title} onChange={e => set('title', e.target.value)} required />
                </div>
                <div>
                  <FieldLabel helper="Internal notes — not shown in the video">Description</FieldLabel>
                  <Textarea placeholder="Optional notes about this project..." rows={3} value={form.description} onChange={e => set('description', e.target.value)} />
                </div>
              </div>
            </Section>

            {/* Audio */}
            <Section icon={Music2} title="Audio" description="Upload audio or generate AI voiceover.">
              <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                <Toggle checked={form.use_tts} onChange={v => set('use_tts', v)}
                  label="Generate Audio with AI (Text-to-Speech)"
                  helper="ON → type text below. OFF → upload your audio file." />

                {!form.use_tts && (
                  <>
                    <div>
                      <FieldLabel required helper="MP3, WAV, M4A — max 50 MB">Audio File</FieldLabel>
                      <DropZone accept="audio/mpeg,audio/wav,audio/mp4,audio/x-m4a"
                        helper="MP3 · WAV · M4A — max 50 MB"
                        files={form.audio ? [form.audio] : null}
                        onChange={handleAudioChange} />
                    </div>

                    {form.audio && (
                      <>
                        <AudioWaveformCropper
                          audioFile={form.audio}
                          onDurationReady={handleDurationReady}
                          onRegionChange={handleRegionChange}
                        />

                        {/* ── Transcript box ── */}
                        <div style={{ marginTop: 4, borderRadius: 12, border: `1px solid ${BORDER}`, overflow: 'hidden' }}>
                          {/* Header */}
                          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', backgroundColor: '#f8fafc', borderBottom: `1px solid ${BORDER}` }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
                              <Mic size={14} color={C3} />
                              <span style={{ fontSize: 12, fontWeight: 600, color: C1, letterSpacing: '0.02em' }}>Audio Transcript</span>
                              {form.transcript && <CheckCircle2 size={13} color="#22c55e" />}
                            </div>
                            <button
                              type="button"
                              onClick={handleTranscribe}
                              disabled={transcribing}
                              style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '6px 14px', fontSize: 12, fontWeight: 600, borderRadius: 7, border: 'none', backgroundColor: transcribing ? '#e2e8f0' : C3, color: transcribing ? '#94a3b8' : 'white', cursor: transcribing ? 'not-allowed' : 'pointer', transition: 'all 0.2s' }}
                              onMouseEnter={e => { if (!transcribing) e.currentTarget.style.backgroundColor = C1 }}
                              onMouseLeave={e => { if (!transcribing) e.currentTarget.style.backgroundColor = C3 }}
                            >
                              {transcribing
                                ? <><Loader2 size={12} style={{ animation: 'spin 1s linear infinite' }} /> Transcribing…</>
                                : <><Mic size={12} /> {form.transcript ? 'Re-transcribe' : 'Transcribe (Urdu)'}</>
                              }
                            </button>
                          </div>

                          {/* Textarea */}
                          <div style={{ padding: '10px 14px', backgroundColor: 'white' }}>
                            {transcribing && (
                              <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '12px 0', color: '#6b7280', fontSize: 12 }}>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ animation: 'spin 1s linear infinite', flexShrink: 0 }}>
                                  <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                                </svg>
                                OpenAI Whisper audio transcribe kar raha hai — ye kuch waqt le sakta hai…
                              </div>
                            )}
                            <textarea
                              value={form.transcript}
                              onChange={e => set('transcript', e.target.value)}
                              rows={5}
                              placeholder={transcribing ? '' : 'یہاں لکھیں'}
                              style={{ ...inputBase, resize: 'vertical', lineHeight: 1.8, direction: 'rtl', textAlign: 'right', fontFamily: 'inherit', display: transcribing ? 'none' : 'block' }}
                              onFocus={e => { e.target.style.borderColor = C3; e.target.style.boxShadow = `0 0 0 3px rgba(80,80,129,0.1)` }}
                              onBlur={e => { e.target.style.borderColor = BORDER; e.target.style.boxShadow = 'none' }}
                            />
                            {transcribeErr && (
                              <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8, padding: '8px 12px', backgroundColor: '#fef2f2', borderRadius: 8, border: '1px solid #fecaca' }}>
                                <AlertCircle size={13} color="#ef4444" />
                                <span style={{ fontSize: 11, color: '#dc2626' }}>{transcribeErr}</span>
                              </div>
                            )}
                            {form.transcript && !transcribing && (
                              <p style={{ fontSize: 10, color: '#9ca3af', marginTop: 6 }}>
                                Text edit karein — yahi subtitle video mein lagega
                              </p>
                            )}
                          </div>
                        </div>
                        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
                      </>
                    )}

                    <Divider />
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                      <div>
                        <FieldLabel helper={form.audio ? 'Auto-filled from waveform selection' : 'Auto-filled when audio is uploaded'}>Trim Start</FieldLabel>
                        <div style={{ position: 'relative' }}>
                          <Input type="number" min={0} placeholder="0" value={form.trim_start} onChange={e => set('trim_start', e.target.value)} style={{ paddingRight: 42, backgroundColor: form.audio ? 'rgba(80,80,129,0.04)' : undefined }} />
                          <span style={{ position: 'absolute', right: 12, top: '50%', transform: 'translateY(-50%)', fontSize: 11, color: HELPER_COLOR, fontWeight: 500 }}>sec</span>
                        </div>
                      </div>
                      <div>
                        <FieldLabel helper={form.audio ? 'Auto-filled from waveform selection' : 'Leave empty for full audio'}>Trim End</FieldLabel>
                        <div style={{ position: 'relative' }}>
                          <Input type="number" min={1} placeholder="Full audio" value={form.trim_end} onChange={e => set('trim_end', e.target.value)} style={{ paddingRight: 42, backgroundColor: form.audio ? 'rgba(80,80,129,0.04)' : undefined }} />
                          <span style={{ position: 'absolute', right: 12, top: '50%', transform: 'translateY(-50%)', fontSize: 11, color: HELPER_COLOR, fontWeight: 500 }}>sec</span>
                        </div>
                      </div>
                    </div>
                  </>
                )}

                {form.use_tts && (
                  <>
                    <div>
                      <FieldLabel required helper="This text will be converted to voice">Text for AI Voice</FieldLabel>
                      <Textarea placeholder="Bismillah hir Rahman nir Raheem..." rows={5} value={form.tts_text} onChange={e => set('tts_text', e.target.value)} />
                    </div>
                    <div>
                      <FieldLabel helper="Choose the AI voice style">AI Voice</FieldLabel>
                      <Select value={form.tts_voice} onChange={e => set('tts_voice', e.target.value)}>
                        <option value="alloy">🎙️ Alloy — Neutral</option>
                        <option value="echo">🎙️ Echo — Male</option>
                        <option value="fable">🎙️ Fable — British</option>
                        <option value="onyx">🎙️ Onyx — Deep Male</option>
                        <option value="nova">🎙️ Nova — Female</option>
                        <option value="shimmer">🎙️ Shimmer — Soft Female</option>
                      </Select>
                    </div>
                  </>
                )}
              </div>
            </Section>

            {/* Subtitles */}
            <Section icon={AlignLeft} title="Subtitles" description="Auto-generate via OpenAI Whisper — requires paid API key." collapsible defaultOpen={false}>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                <Toggle checked={form.generate_subtitles} onChange={v => set('generate_subtitles', v)}
                  label="Generate Subtitles"
                  helper="Whisper AI will transcribe your audio into on-screen subtitles." />
                {form.generate_subtitles && (
                  <div>
                    <FieldLabel>Subtitle Language</FieldLabel>
                    <Select value={form.subtitle_language} onChange={e => set('subtitle_language', e.target.value)}>
                      <option value="ur">🇵🇰 Urdu</option>
                      <option value="en">🇬🇧 English</option>
                      <option value="ar">🇸🇦 Arabic</option>
                    </Select>
                  </div>
                )}
              </div>
            </Section>
          </div>

          {/* RIGHT */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>

            {/* Background Images */}
            <Section icon={ImageIcon} title="Background Images" description="Each image becomes one scene — drag to reorder.">
              <div>
                <FieldLabel required helper="JPEG · PNG · WebP — max 10 MB each — up to 10 files">Images</FieldLabel>
                <DropZone accept="image/jpeg,image/png,image/webp" multiple
                  helper="JPEG · PNG · WebP · max 10 MB · up to 10 files"
                  files={form.images}
                  aspect="9/16"
                  onChange={files => set('images', files)} />
                {(!form.images || form.images.length === 0) && (
                  <p style={{ fontSize: 11, color: HELPER_COLOR, marginTop: 8, fontStyle: 'italic' }}>
                    Upload images — crop controls will appear here.
                  </p>
                )}

                <ImageCropPanel
                  files={form.images}
                  resolution={form.video_resolution}
                  croppedData={form.croppedImages}
                  onCropChange={(index, blob, url) =>
                    set('croppedImages', { ...form.croppedImages, [index]: { blob, url } })
                  }
                />
              </div>
            </Section>

            {/* Animation & Video */}
            <Section icon={PlayCircle} title="Animation & Video" description="Set animation style, image duration, and output resolution.">
              <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                <div>
                  <FieldLabel>Animation Style</FieldLabel>
                  <Select value={form.animation_type} onChange={e => set('animation_type', e.target.value)}>
                    <option value="ken_burns">🔍 Ken Burns — Slow Zoom In</option>
                    <option value="zoom_out">🔎 Zoom Out</option>
                    <option value="fade">✨ Fade In / Out</option>
                    <option value="static">🖼️ Static — No Animation</option>
                  </Select>
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, alignItems: 'end' }}>
                  <div>
                    <FieldLabel helper="Auto-calculated when audio is uploaded">Seconds Per Image</FieldLabel>
                    <div style={{ position: 'relative' }}>
                      <Input type="number" min={3} max={30} value={form.image_duration} onChange={e => set('image_duration', e.target.value)} style={{ paddingRight: 42 }} />
                      <span style={{ position: 'absolute', right: 12, top: '50%', transform: 'translateY(-50%)', fontSize: 11, color: HELPER_COLOR, fontWeight: 500 }}>sec</span>
                    </div>
                  </div>
                  <div>
                    <FieldLabel helper="Final video width × height in pixels">Output Resolution</FieldLabel>
                    <Select value={form.video_resolution} onChange={e => set('video_resolution', e.target.value)}>
                      <option value="576x1024">📱 576×1024 — Portrait</option>
                      <option value="1080x1920">📱 1080×1920 — HD Portrait</option>
                      <option value="1080x1080">⬜ 1080×1080 — Square</option>
                      <option value="1920x1080">🖥️ 1920×1080 — Landscape</option>
                    </Select>
                  </div>
                </div>
              </div>
            </Section>

            {/* Branding */}
            <Section icon={Bookmark} title="Branding & End Card" description="Add watermark and social handles at the end." collapsible defaultOpen={false}>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                <div>
                  <FieldLabel helper="Shown as a subtle overlay on the video">Watermark Text</FieldLabel>
                  <Input placeholder="@YourUsername" value={form.watermark_text} onChange={e => set('watermark_text', e.target.value)} />
                </div>
                <Toggle checked={form.show_end_card} onChange={v => set('show_end_card', v)}
                  label="Show End Card"
                  helper="Display your social media handles at the end of the video." />
              </div>
            </Section>

            {/* Info card */}
            <div style={{ display: 'flex', gap: 12, padding: '14px 16px', backgroundColor: 'rgba(80,80,129,0.06)', borderRadius: 12, border: '1px solid rgba(80,80,129,0.15)' }}>
              <Sparkles size={18} color={C3} style={{ flexShrink: 0, marginTop: 1 }} />
              <div>
                <p style={{ fontSize: 12, fontWeight: 600, color: C1 }}>How it works</p>
                <p style={{ fontSize: 11, color: '#6b7280', marginTop: 3, lineHeight: 1.6 }}>
                  Save your project → it joins the queue → FFmpeg assembles images + audio → your video is ready to download.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* ── Bottom bar ── */}
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10, marginTop: 24, paddingTop: 20, borderTop: `1px solid ${BORDER}` }}>
          <button type="button" onClick={() => navigate('/my-videos')}
            style={{ padding: '10px 20px', fontSize: 13, fontWeight: 500, borderRadius: 9, border: `1px solid ${BORDER}`, backgroundColor: 'white', cursor: 'pointer', color: '#64748b' }}>
            Cancel
          </button>
          <button type="submit" disabled={saving}
            style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '10px 28px', backgroundColor: C3, color: 'white', fontSize: 13, fontWeight: 600, borderRadius: 9, border: 'none', cursor: saving ? 'not-allowed' : 'pointer', opacity: saving ? 0.7 : 1, boxShadow: '0 2px 8px rgba(80,80,129,0.35)' }}
            onMouseEnter={e => { if (!saving) e.currentTarget.style.backgroundColor = C1 }}
            onMouseLeave={e => { if (!saving) e.currentTarget.style.backgroundColor = C3 }}
          >
            {saving ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
            {saving ? 'Saving…' : 'Save & Generate Video'}
          </button>
        </div>
      </form>
    </div>
  )
}
