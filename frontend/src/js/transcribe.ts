import { initFileUploadForm } from './file-upload-form'

initFileUploadForm({
  apiUrl: 'https://api.softcatala.org/transcribe-service/v1',
  apiPath: 'transcribe_file',
  buttonLabel: 'Demana la transcripció',
  successMessage: (waitingQueue) => {
    if (waitingQueue === 0) return 'El vostre fitxer és el següent en la cua de transcripció.'
    if (waitingQueue === 1) return 'El vostre fitxer té només un fitxer per davant en la cua de transcripció.'
    return `El vostre fitxer té ${waitingQueue} fitxers per davant en la cua de transcripció.`
  },
})
