import { useState, useRef, useCallback } from 'react'
import ReactCrop, { centerCrop, makeAspectCrop } from 'react-image-crop'
import 'react-image-crop/dist/ReactCrop.css'
import { Crop, Check, X, ZoomIn } from 'lucide-react'

const C1 = '#272757'
const C3 = '#505081'

function getAspect(resolution) {
  const [w, h] = resolution.split('x').map(Number)
  return w / h
}

function getResLabel(resolution) {
  const map = {
    '576x1024': '576×1024',
    '1080x1920': '1080×1920',
    '1080x1080': '1080×1080',
    '1920x1080': '1920×1080',
  }
  return map[resolution] ?? resolution
}

// Draw cropped canvas and return blob
async function getCroppedBlob(imgEl, crop, resolution) {
  const [tw, th] = resolution.split('x').map(Number)
  const scaleX = imgEl.naturalWidth  / imgEl.width
  const scaleY = imgEl.naturalHeight / imgEl.height

  const canvas = document.createElement('canvas')
  canvas.width  = tw
  canvas.height = th
  const ctx = canvas.getContext('2d')

  ctx.drawImage(
    imgEl,
    crop.x * scaleX, crop.y * scaleY,
    crop.width * scaleX, crop.height * scaleY,
    0, 0, tw, th
  )

  return new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.92))
}

// ── Single image card ──────────────────────────────────────────────
function ImageCard({ file, resolution, onCropped, croppedUrl }) {
  const [modalOpen, setModalOpen] = useState(false)
  const [crop, setCrop] = useState()
  const [completedCrop, setCompletedCrop] = useState()
  const imgRef = useRef(null)
  const aspect = getAspect(resolution)

  const srcUrl = croppedUrl ?? URL.createObjectURL(file)

  const onImageLoad = useCallback((e) => {
    const { width, height } = e.currentTarget
    // Calculate in pixels — 65% of image width, height locked to aspect ratio
    let cropW = width * 0.65
    let cropH = cropW / aspect
    // If height exceeds image, scale down by height instead
    if (cropH > height * 0.65) {
      cropH = height * 0.65
      cropW = cropH * aspect
    }
    setCrop({
      unit: 'px',
      x: (width  - cropW) / 2,
      y: (height - cropH) / 2,
      width:  cropW,
      height: cropH,
    })
  }, [aspect])

  const handleSave = async () => {
    if (!completedCrop || !imgRef.current) return
    const blob = await getCroppedBlob(imgRef.current, completedCrop, resolution)
    const url = URL.createObjectURL(blob)
    onCropped(blob, url)
    setModalOpen(false)
  }

  const [w, h] = resolution.split('x').map(Number)

  return (
    <>
      {/* Thumbnail */}
      <div
        onClick={() => setModalOpen(true)}
        style={{
          aspectRatio: `${w} / ${h}`,
          borderRadius: 10,
          overflow: 'hidden',
          border: `2px solid ${croppedUrl ? '#22c55e' : '#e2e8f0'}`,
          position: 'relative',
          cursor: 'pointer',
          backgroundColor: '#f1f5f9',
          boxShadow: '0 1px 4px rgba(0,0,0,0.08)',
          transition: 'border-color 0.2s, transform 0.15s',
        }}
        onMouseEnter={e => e.currentTarget.style.transform = 'scale(1.03)'}
        onMouseLeave={e => e.currentTarget.style.transform = 'scale(1)'}
      >
        <img src={srcUrl} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }} />

        {/* Hover overlay */}
        <div style={{
          position: 'absolute', inset: 0,
          backgroundColor: 'rgba(0,0,0,0.45)',
          display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 6,
          opacity: 0, transition: 'opacity 0.2s',
        }}
          onMouseEnter={e => e.currentTarget.style.opacity = 1}
          onMouseLeave={e => e.currentTarget.style.opacity = 0}
        >
          <Crop size={18} color="white" />
          <span style={{ fontSize: 11, fontWeight: 600, color: 'white' }}>Crop</span>
        </div>

        {/* Status badge */}
        <div style={{
          position: 'absolute', top: 5, right: 5,
          padding: '2px 7px', borderRadius: 20, fontSize: 10, fontWeight: 700,
          backgroundColor: croppedUrl ? '#22c55e' : 'rgba(0,0,0,0.55)',
          color: 'white',
        }}>
          {croppedUrl ? '✓ Cropped' : 'Tap to crop'}
        </div>
      </div>

      {/* Crop Modal */}
      {modalOpen && (
        <div style={{
          position: 'fixed', inset: 0, zIndex: 9999,
          backgroundColor: 'rgba(0,0,0,0.82)',
          display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20,
        }}
          onClick={e => { if (e.target === e.currentTarget) setModalOpen(false) }}
        >
          <div style={{
            backgroundColor: 'white', borderRadius: 16,
            width: 'min(700px, 95vw)', maxHeight: '92vh',
            display: 'flex', flexDirection: 'column', overflow: 'hidden',
            boxShadow: '0 24px 60px rgba(0,0,0,0.4)',
          }}>
            {/* Modal header */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 20px', borderBottom: '1px solid #e2e8f0' }}>
              <div>
                <p style={{ fontWeight: 700, fontSize: 14, color: C1 }}>Crop Image</p>
                <p style={{ fontSize: 11, color: '#6b7280', marginTop: 2 }}>
                  Output: <strong>{getResLabel(resolution)}</strong> px · Drag handles to adjust crop area
                </p>
              </div>
              <button onClick={() => setModalOpen(false)}
                style={{ width: 30, height: 30, borderRadius: '50%', border: 'none', backgroundColor: '#f1f5f9', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b' }}>
                <X size={16} />
              </button>
            </div>

            {/* Instructions bar */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 20, padding: '7px 20px', backgroundColor: '#f8fafc', borderBottom: '1px solid #e2e8f0', flexWrap: 'wrap' }}>
              {[
                { icon: '✥', text: 'Drag inside box to move' },
                { icon: '⤡', text: 'Drag corners/edges to resize' },
                { icon: '＋', text: 'Click outside to redraw' },
              ].map(({ icon, text }) => (
                <span key={text} style={{ fontSize: 11, color: '#6b7280', display: 'flex', alignItems: 'center', gap: 5 }}>
                  <span style={{ fontSize: 14, color: C3 }}>{icon}</span> {text}
                </span>
              ))}
            </div>

            {/* Cropper area */}
            <div style={{ flex: 1, overflow: 'auto', backgroundColor: '#0f0e47', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16, minHeight: 300 }}>
              <ReactCrop
                crop={crop}
                onChange={(c) => setCrop(c)}
                onComplete={(c) => setCompletedCrop(c)}
                aspect={aspect}
                ruleOfThirds
                style={{ maxHeight: 'calc(92vh - 200px)' }}
              >
                <img
                  ref={imgRef}
                  src={URL.createObjectURL(file)}
                  onLoad={onImageLoad}
                  alt="crop"
                  style={{ maxWidth: '100%', maxHeight: 'calc(92vh - 160px)', display: 'block' }}
                  crossOrigin="anonymous"
                />
              </ReactCrop>
            </div>

            {/* Modal footer */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 20px', borderTop: '1px solid #e2e8f0' }}>
              <p style={{ fontSize: 11, color: '#9ca3af' }}>
                Cropped output → {getResLabel(resolution)} px JPEG
              </p>
              <div style={{ display: 'flex', gap: 8 }}>
                <button onClick={() => setModalOpen(false)}
                  style={{ padding: '8px 16px', fontSize: 13, borderRadius: 8, border: '1px solid #e2e8f0', backgroundColor: 'white', cursor: 'pointer', color: '#64748b' }}>
                  Cancel
                </button>
                <button onClick={handleSave}
                  style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '8px 18px', fontSize: 13, fontWeight: 600, borderRadius: 8, border: 'none', backgroundColor: C3, color: 'white', cursor: 'pointer' }}
                  onMouseEnter={e => e.currentTarget.style.backgroundColor = C1}
                  onMouseLeave={e => e.currentTarget.style.backgroundColor = C3}
                >
                  <Check size={14} /> Save Crop
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  )
}

// ── Main export ────────────────────────────────────────────────────
export default function ImageCropPanel({ files, resolution, croppedData, onCropChange }) {
  if (!files || files.length === 0) return null
  const [w, h] = resolution.split('x').map(Number)

  return (
    <div style={{ marginTop: 14 }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}>
        <p style={{ fontSize: 12, fontWeight: 600, color: C1, textTransform: 'uppercase', letterSpacing: '0.04em' }}>
          Crop Images ({files.length})
        </p>
        <span style={{ fontSize: 11, color: '#9ca3af' }}>
          Aspect ratio: {w}:{h} · Click any image to crop
        </span>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))', gap: 10 }}>
        {Array.from(files).map((file, i) => (
          <ImageCard
            key={i}
            file={file}
            resolution={resolution}
            croppedUrl={croppedData[i]?.url ?? null}
            onCropped={(blob, url) => onCropChange(i, blob, url)}
          />
        ))}
      </div>
      <p style={{ fontSize: 11, color: '#9ca3af', marginTop: 8 }}>
        {Object.keys(croppedData).length} / {files.length} images cropped
      </p>
    </div>
  )
}
