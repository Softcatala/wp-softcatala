import { initFileUploadForm } from './file-upload-form'

initFileUploadForm({
  apiUrl: 'https://api.softcatala.org/dubbing-service/v1',
  apiPath: 'dubbing_file',
  buttonLabel: 'Demana el doblatge',
  successMessage: (waitingQueue, filename) => {
    const f = filename ? `'${filename}'` : 'el vostre fitxer'
    if (waitingQueue === 0) return `El fitxer ${f} és el següent en la cua de doblatge.`
    if (waitingQueue === 1) return `El fitxer ${f} té només un fitxer per davant en la cua de doblatge.`
    return `El fitxer ${f} té ${waitingQueue} fitxers per davant en la cua de doblatge.`
  },
})
