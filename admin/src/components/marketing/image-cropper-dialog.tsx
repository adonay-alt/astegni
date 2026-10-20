'use client'

import React, { useState, useCallback, useEffect } from 'react'
import Cropper, { Area, Point } from 'react-easy-crop'
import 'react-easy-crop/react-easy-crop.css'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ZoomIn, ZoomOut, Crop, Check, X, RotateCcw } from 'lucide-react'
import { getCroppedImg, PixelCrop } from '@/lib/crop-image'

interface ImageCropperDialogProps {
  isOpen: boolean
  imageSrc: string | null
  onClose: () => void
  onCropComplete: (croppedFile: File, previewUrl: string) => void
  defaultAspect?: number
}

export function ImageCropperDialog({
  isOpen,
  imageSrc,
  onClose,
  onCropComplete,
  defaultAspect = 16 / 9,
}: ImageCropperDialogProps) {
  const [crop, setCrop] = useState<Point>({ x: 0, y: 0 })
  const [zoom, setZoom] = useState(1)
  const [aspect, setAspect] = useState<number>(defaultAspect)
  const [croppedAreaPixels, setCroppedAreaPixels] = useState<PixelCrop | null>(null)
  const [isProcessing, setIsProcessing] = useState(false)

  // Reset controls whenever dialog opens with an image
  useEffect(() => {
    if (isOpen) {
      setCrop({ x: 0, y: 0 })
      setZoom(1)
      setAspect(defaultAspect)
      setIsProcessing(false)
    }
  }, [isOpen, imageSrc, defaultAspect])

  const onCropChange = (newCrop: Point) => setCrop(newCrop)
  const onZoomChange = (newZoom: number) => setZoom(newZoom)

  const handleCropComplete = useCallback(
    (_croppedArea: Area, pixelCrop: Area) => {
      setCroppedAreaPixels(pixelCrop)
    },
    []
  )

  const handleApplyCrop = async () => {
    if (!imageSrc || !croppedAreaPixels) return
    setIsProcessing(true)
    try {
      const { file, url } = await getCroppedImg(
        imageSrc,
        croppedAreaPixels,
        'cropped-banner.jpg'
      )
      onCropComplete(file, url)
      onClose()
    } catch (e) {
      console.error('Error cropping image:', e)
    } finally {
      setIsProcessing(false)
    }
  }

  const handleReset = () => {
    setCrop({ x: 0, y: 0 })
    setZoom(1)
  }

  if (!imageSrc) return null

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && !isProcessing && onClose()}>
      <DialogContent className="max-w-2xl border-slate-800 bg-slate-900 text-white p-6 shadow-2xl sm:rounded-xl">
        <DialogHeader>
          <DialogTitle className="flex items-center justify-between text-base font-semibold text-slate-100">
            <div className="flex items-center gap-2">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600/20 text-blue-400">
                <Crop className="h-4 w-4" />
              </div>
              <div>
                <span>Resize & Crop Image</span>
                <p className="text-xs font-normal text-slate-400">
                  Drag to reposition or scroll to zoom (Instagram-style)
                </p>
              </div>
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={handleReset}
              className="text-xs text-slate-400 hover:text-white hover:bg-slate-800 h-7 px-2"
              title="Reset position and zoom"
            >
              <RotateCcw className="h-3 w-3 mr-1" />
              Reset
            </Button>
          </DialogTitle>
        </DialogHeader>

        {/* Cropper Container */}
        <div className="relative h-80 w-full overflow-hidden rounded-lg bg-black border border-slate-800 select-none">
          <Cropper
            image={imageSrc}
            crop={crop}
            zoom={zoom}
            aspect={aspect}
            minZoom={1}
            maxZoom={3}
            onCropChange={onCropChange}
            onCropComplete={handleCropComplete}
            onZoomChange={onZoomChange}
            showGrid={true}
            zoomWithScroll={true}
          />
        </div>

        {/* Aspect Ratio Presets */}
        <div className="flex flex-wrap items-center justify-between gap-3 pt-1 border-t border-slate-800/80">
          <div className="flex items-center gap-2">
            <span className="text-xs text-slate-400 font-medium">Aspect Ratio:</span>
            <Button
              type="button"
              size="sm"
              variant={Math.abs(aspect - 16 / 9) < 0.01 ? 'default' : 'outline'}
              className={`h-7 text-xs ${
                Math.abs(aspect - 16 / 9) < 0.01
                  ? 'bg-blue-600 hover:bg-blue-500 text-white'
                  : 'border-slate-700 bg-slate-800/60 text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
              onClick={() => setAspect(16 / 9)}
            >
              16:9 Banner
            </Button>
            <Button
              type="button"
              size="sm"
              variant={Math.abs(aspect - 1) < 0.01 ? 'default' : 'outline'}
              className={`h-7 text-xs ${
                Math.abs(aspect - 1) < 0.01
                  ? 'bg-blue-600 hover:bg-blue-500 text-white'
                  : 'border-slate-700 bg-slate-800/60 text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
              onClick={() => setAspect(1)}
            >
              1:1 Square
            </Button>
            <Button
              type="button"
              size="sm"
              variant={Math.abs(aspect - 4 / 3) < 0.01 ? 'default' : 'outline'}
              className={`h-7 text-xs ${
                Math.abs(aspect - 4 / 3) < 0.01
                  ? 'bg-blue-600 hover:bg-blue-500 text-white'
                  : 'border-slate-700 bg-slate-800/60 text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
              onClick={() => setAspect(4 / 3)}
            >
              4:3 Standard
            </Button>
          </div>
        </div>

        {/* Zoom Controls */}
        <div className="flex items-center gap-3 px-3 py-1.5 bg-slate-950/60 rounded-lg border border-slate-800/60">
          <ZoomOut className="h-4 w-4 text-slate-400 shrink-0" />
          <input
            type="range"
            min={1}
            max={3}
            step={0.02}
            value={zoom}
            onChange={(e) => setZoom(Number(e.target.value))}
            className="w-full accent-blue-500 h-1.5 bg-slate-800 rounded-lg cursor-pointer"
          />
          <ZoomIn className="h-4 w-4 text-slate-400 shrink-0" />
          <span className="text-xs font-mono text-slate-300 min-w-10 text-right">
            {Math.round(zoom * 100)}%
          </span>
        </div>

        <DialogFooter className="flex items-center justify-end gap-2 pt-2">
          <Button
            type="button"
            variant="ghost"
            onClick={onClose}
            disabled={isProcessing}
            className="text-slate-300 hover:bg-slate-800 hover:text-white"
          >
            <X className="mr-1.5 h-4 w-4" /> Cancel
          </Button>
          <Button
            type="button"
            onClick={handleApplyCrop}
            disabled={isProcessing}
            className="bg-blue-600 hover:bg-blue-500 text-white font-medium"
          >
            <Check className="mr-1.5 h-4 w-4" />
            {isProcessing ? 'Processing...' : 'Apply Crop & Resize'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
