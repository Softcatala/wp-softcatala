const API_URL = 'https://api.softcatala.org/transcribe-service/v1'

const EMAIL_RE =
  /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/

function validateEmail(email: string): boolean {
  return EMAIL_RE.test(email.toLowerCase())
}

function qs<T extends Element>(selector: string): T {
  const el = document.querySelector<T>(selector)
  if (!el) throw new Error(`Element not found: ${selector}`)
  return el
}

function displayError(msg: string): void {
  qs<HTMLElement>('#info').classList.remove('visible')
  qs<HTMLElement>('#errormessage').innerHTML = msg
  qs<HTMLElement>('#error').classList.add('visible')
}

function displayOk(message1: string, message2: string): void {
  qs<HTMLElement>('#info_text1').textContent = message1
  qs<HTMLElement>('#info_text2').textContent = message2
  qs<HTMLElement>('#error').classList.remove('visible')
  qs<HTMLElement>('#info').classList.add('visible')
}

function getUrlParams(): URLSearchParams {
  return new URLSearchParams(window.location.search)
}

function getDownloadURL(ext: string): string {
  const uuid = getUrlParams().get('uuid') ?? ''
  return `${API_URL}/get_file/?uuid=${uuid}&ext=${ext}`
}

function setLinks(): void {
  qs<HTMLAnchorElement>('#txt_down').href = getDownloadURL('txt')
  qs<HTMLAnchorElement>('#srt_down').href = getDownloadURL('srt')
  qs<HTMLAnchorElement>('#json_down').href = getDownloadURL('json')
}

function showFound(): void {
  qs<HTMLElement>('#found').classList.remove('hidden')
  qs<HTMLElement>('#notfound').classList.add('hidden')
}

function showNotFound(): void {
  qs<HTMLElement>('#found').classList.add('hidden')
  qs<HTMLElement>('#notfound').classList.remove('hidden')
}

function checkUuid(): void {
  const uuid = getUrlParams().get('uuid')
  if (!uuid) {
    showNotFound()
    return
  }

  fetch(`${API_URL}/uuid_exists/?uuid=${uuid}`)
    .then(r => { r.ok ? showFound() : showNotFound() })
    .catch(() => showNotFound())
}

function deleteFile(): void {
  const uuid = getUrlParams().get('uuid') ?? ''
  const formData = new FormData(qs<HTMLFormElement>('#form-id'))
  formData.append('uuid', uuid)

  fetch(`${API_URL}/delete_uuid/`, { method: 'POST', body: formData })
    .then(async r => {
      if (r.ok) {
        displayOk(
          "La transcripció i tots els fitxers associats s'han esborrat",
          'Torneu a recarregar la pàgina.'
        )
        qs<HTMLAnchorElement>('#txt_down').removeAttribute('href')
        qs<HTMLAnchorElement>('#srt_down').removeAttribute('href')
        qs<HTMLAnchorElement>('#json_down').removeAttribute('href')
        qs<HTMLElement>('#editor').style.display = 'none'
      } else {
        const json = await r.json()
        displayError('Error: ' + (json['error'] ?? 'Error desconegut'))
      }
    })
    .catch(() => displayError("S'ha produït un error inesperat."))
}

document.addEventListener('DOMContentLoaded', () => {
  checkUuid()
  setLinks()

  qs<HTMLInputElement>('#mostra_opcions_esborrat').addEventListener('change', function () {
    qs<HTMLElement>('#esborrat_controls').style.display = this.checked ? 'block' : 'none'
  })

  qs<HTMLButtonElement>('#i_esborra').addEventListener('click', () => {
    const email = qs<HTMLInputElement>('#email').value
    if (!validateEmail(email)) {
      displayError('Reviseu la vostra adreça electrònica.')
      qs<HTMLInputElement>('#email').focus()
    } else {
      deleteFile()
    }
  })

  qs('#error .close').addEventListener('click', () => {
    qs<HTMLElement>('#error').classList.remove('visible')
  })

  qs('#info .close').addEventListener('click', () => {
    qs<HTMLElement>('#info').classList.remove('visible')
  })
})
