/**
 * Shared file-upload form logic for transcription and dubbing pages.
 *
 * Handles validation, XHR upload with progress, and success/error display.
 * Instantiated by transcribe.ts and dubbing.ts with service-specific config.
 */

export interface FileUploadConfig {
  /** Base API URL, e.g. https://api.softcatala.org/dubbing-service/v1 */
  apiUrl: string
  /** API path segment, e.g. dubbing_file or transcribe_file */
  apiPath: string
  /** Label for the submit button */
  buttonLabel: string
  /** Build the success message shown to the user */
  successMessage: (waitingQueue: number, filename?: string) => string
}

const EMAIL_RE =
  /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/

function validateEmail(email: string): boolean {
  return EMAIL_RE.test(email.toLowerCase())
}

function qs<T extends Element>(selector: string): T {
  const el = document.querySelector<T>(selector)
  if (!el) throw new Error(`Element not found: ${selector}`)
  return el
}

export function initFileUploadForm(config: FileUploadConfig): void {
  const { apiUrl, apiPath, buttonLabel, successMessage } = config
  const progress = document.querySelectorAll<HTMLElement>('.progress, .progress-bar')

  function displayError(msg: string): void {
    qs<HTMLButtonElement>('#i_demana').textContent = buttonLabel
    qs<HTMLElement>('#info').style.display = 'none'
    qs<HTMLElement>('#error').classList.remove('hidden')
    qs<HTMLElement>('#errormessage').innerHTML = msg
    qs<HTMLElement>('#error').style.display = 'block'
  }

  function displaySuccess(waitingQueue: number, filename?: string): void {
    qs<HTMLButtonElement>('#i_demana').textContent = buttonLabel
    qs<HTMLElement>('#info_text1').textContent =
      successMessage(waitingQueue, filename) +
      " D'aquí a una estona rebreu un correu electrònic quan el fitxer estigui llest."
    qs<HTMLElement>('#info_text2').textContent = 'Gràcies per usar aquest servei.'
    qs<HTMLElement>('#error').style.display = 'none'
    qs<HTMLElement>('#info').classList.remove('hidden')
    qs<HTMLElement>('#info').style.display = 'block'
    const emailInput = document.querySelector<HTMLInputElement>('#n_email')
    const fileInput = document.querySelector<HTMLInputElement>('#n_file')
    if (emailInput) emailInput.value = ''
    if (fileInput) fileInput.value = ''
  }

  function updateProgress(evt: ProgressEvent): void {
    if (!evt.lengthComputable) return
    const pct = Math.ceil((evt.loaded / evt.total) * 100)
    const pctVal = pct + '%'
    const bar = document.querySelector<HTMLElement>('#bar')
    const percent = document.querySelector<HTMLElement>('#percent')
    if (bar) bar.style.width = pctVal
    if (percent) percent.textContent = pctVal
  }

  function sendFile(): void {
    const xhr = new XMLHttpRequest()
    xhr.upload.onprogress = updateProgress

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return

      const bar = document.querySelector<HTMLElement>('#bar')
      const percent = document.querySelector<HTMLElement>('#percent')
      if (bar) bar.style.width = '0%'
      if (percent) percent.textContent = '0 %'
      progress.forEach((el) => (el.style.display = 'none'))

      const json = JSON.parse(xhr.responseText)
      if (xhr.status === 200) {
        document.querySelector<HTMLInputElement>('#file')!.value = ''
        displaySuccess(json['waiting_queue'], json['filename'])
      } else {
        displayError(json['error'])
      }
    }

    const formData = new FormData(qs<HTMLFormElement>('#form-id'))
    xhr.open('post', `${apiUrl}/${apiPath}/`)
    progress.forEach((el) => (el.style.display = 'block'))
    const bar = document.querySelector<HTMLElement>('#bar')
    const percent = document.querySelector<HTMLElement>('#percent')
    if (bar) bar.style.width = '0%'
    if (percent) percent.textContent = '0 %'
    xhr.send(formData)
  }

  qs<HTMLButtonElement>('#i_demana').addEventListener('click', () => {
    const email = qs<HTMLInputElement>('#email').value
    const file = qs<HTMLInputElement>('#file').value

    if (!validateEmail(email)) {
      displayError('Reviseu la vostra adreça electrònica.')
      qs<HTMLInputElement>('#email').focus()
    } else if (!file) {
      displayError("No s'ha especificat cap fitxer")
      qs<HTMLInputElement>('#file').focus()
    } else {
      sendFile()
    }
  })

  qs('#error > button').addEventListener('click', () => {
    qs<HTMLElement>('#error').style.display = 'none'
  })

  qs('#info > button').addEventListener('click', () => {
    qs<HTMLElement>('#info').style.display = 'none'
  })

  // SRT options toggle (transcribe page only — no-op if element absent)
  const srtToggle = document.querySelector<HTMLInputElement>('#mostra_opcions')
  const srtOptions = document.querySelector<HTMLElement>('#srt_options')
  if (srtToggle && srtOptions) {
    srtToggle.addEventListener('change', () => {
      srtOptions.style.display = srtToggle.checked ? 'block' : 'none'
    })
  }
}
