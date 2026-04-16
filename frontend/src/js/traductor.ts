/**
 * Traductor de Softcatalà
 *
 * Vanilla TypeScript port of static/js/traductor.js.
 * No jQuery. No external dependencies.
 * Cookie logic ported inline from jquery.metacookie.js.
 *
 * Wrapped in an IIFE so all declarations are locally scoped and do not
 * clash with main.min.js when both are loaded as classic scripts.
 */

// ---------------------------------------------------------------------------
// Types (ambient — must stay outside the IIFE)
// ---------------------------------------------------------------------------

interface ScSettings {
  log_traductor_source?: boolean;
}

declare const sc_settings: ScSettings;

(function () {

// ---------------------------------------------------------------------------
// MetaCookie — vanilla port of jquery.metacookie.js
// ---------------------------------------------------------------------------

const COOKIE_NAME = 'sc-traductor';
const MAJOR_DELIM = '|';
const MINOR_DELIM = '=';

function setCookie(name: string, value: string, expiresDays = 365): void {
  const expDate = new Date();
  expDate.setTime(expDate.getTime() + expiresDays * 24 * 60 * 60 * 1000);
  const secure = document.location.hostname !== 'softcatala.local' ? '; Secure' : '';
  document.cookie =
    `${name}=${encodeURIComponent(value)}` +
    `; expires=${expDate.toUTCString()}` +
    `; path=/` +
    `; domain=${window.location.hostname}` +
    `; SameSite=Strict` +
    secure;
}

function getCookie(name: string): string {
  const cookies = document.cookie.split(/;\s*/);
  for (const cookie of cookies) {
    const [key, val] = cookie.split('=');
    if (key === name) return decodeURIComponent(val ?? '');
  }
  return '';
}

function getMetaCookie(subName: string, cookieName: string): string {
  const cookieStr = getCookie(cookieName);
  const parts = cookieStr.split(MAJOR_DELIM);
  for (const part of parts) {
    const [key, val] = part.split(MINOR_DELIM);
    if (key === subName) return val ?? '';
  }
  return '';
}

function setMetaCookie(subName: string, cookieName: string, value: string): void {
  const cookieStr = getCookie(cookieName);
  const map: Record<string, string> = {};

  if (cookieStr) {
    for (const part of cookieStr.split(MAJOR_DELIM)) {
      const [k, v] = part.split(MINOR_DELIM);
      if (k && v) map[k] = v;
    }
  }

  if (value) {
    map[subName] = value;
  } else {
    delete map[subName];
  }

  const newVal = Object.entries(map)
    .map(([k, v]) => `${k}${MINOR_DELIM}${v}`)
    .join(MAJOR_DELIM);

  setCookie(cookieName, newVal);
}

// ---------------------------------------------------------------------------
// DOM helpers
// ---------------------------------------------------------------------------

function el<T extends HTMLElement>(id: string): T {
  return document.getElementById(id) as T;
}

function show(element: HTMLElement | null): void {
  if (element) element.style.display = '';
}

function hide(element: HTMLElement | null): void {
  if (element) element.style.display = 'none';
}

function addClass(element: HTMLElement | null, cls: string): void {
  element?.classList.add(cls);
}

function removeClass(element: HTMLElement | null, cls: string): void {
  element?.classList.remove(cls);
}

function enable(element: HTMLButtonElement | HTMLInputElement | HTMLSelectElement | null): void {
  if (element) element.disabled = false;
}

function disable(element: HTMLButtonElement | HTMLInputElement | HTMLSelectElement | null): void {
  if (element) element.disabled = true;
}

// ---------------------------------------------------------------------------
// API endpoints
// ---------------------------------------------------------------------------

let traductor_json_url = 'https://api.softcatala.org/traductor/v1/translate';
let neuronal_json_url = 'https://api.softcatala.org/v2/nmt';

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

let rawText = '';
let scrollAfterTranslation = false;

// ---------------------------------------------------------------------------
// Language button / select helpers
// ---------------------------------------------------------------------------

/**
 * The castellà shortcut button on the origin side is only meaningful when
 * no third language is active on origin. When a third language is active,
 * we move castellà into the origin select as a first option so the user can
 * still reach it. Removing it restores the shortcut button.
 */
function addSpaToOriginSelect(): void {
  const sel = el<HTMLSelectElement>('origin-select');
  if (!sel.querySelector('option[value="spa"]')) {
    const opt = document.createElement('option');
    opt.value = 'spa';
    opt.text = 'castellà';
    sel.insertBefore(opt, sel.firstChild);
  }
}

function removeSpaFromOriginSelect(): void {
  el<HTMLSelectElement>('origin-select')
    .querySelector('option[value="spa"]')
    ?.remove();
}

function addSpaToTargetSelect(): void {
  const sel = el<HTMLSelectElement>('target-select');
  if (!sel.querySelector('option[value="spa"]')) {
    const opt = document.createElement('option');
    opt.value = 'spa';
    opt.text = 'castellà';
    sel.insertBefore(opt, sel.firstChild);
  }
}

function removeSpaFromTargetSelect(): void {
  el<HTMLSelectElement>('target-select')
    .querySelector('option[value="spa"]')
    ?.remove();
}

// ---------------------------------------------------------------------------
// set_origin_button
// ---------------------------------------------------------------------------

function setOriginButton(language: string): void {
  const btnCat = el<HTMLButtonElement>('origin-cat');
  const btnSpa = el<HTMLButtonElement>('origin-spa');
  const sel    = el<HTMLSelectElement>('origin-select');

  if (language === 'spa') {
    addClass(btnSpa, 'select');
    removeClass(btnCat, 'select');
    removeClass(sel, 'select');
    // Restore castellà shortcut button; remove it from select if it was added
    show(btnSpa);
    removeSpaFromOriginSelect();
  } else if (language === 'cat') {
    addClass(btnCat, 'select');
    removeClass(btnSpa, 'select');
    removeClass(sel, 'select');
    show(btnSpa);
    removeSpaFromOriginSelect();
  } else {
    // Third language active on origin: move castellà into the select,
    // hide the shortcut button so the bar fits without overflow
    addClass(sel, 'select');
    removeClass(btnSpa, 'select');
    removeClass(btnCat, 'select');
    sel.value = language;
    hide(btnSpa);
    addSpaToOriginSelect();
  }
}

// ---------------------------------------------------------------------------
// set_target_button
// ---------------------------------------------------------------------------

function setTargetButton(language: string): void {
  const btnCat = el<HTMLButtonElement>('target-cat');
  const btnSpa = el<HTMLButtonElement>('target-spa');
  const sel    = el<HTMLSelectElement>('target-select');

  if (language === 'cat') {
    enable(btnCat);
    addClass(btnCat, 'select');
    removeClass(btnSpa, 'select');
    removeClass(sel, 'select');
    enable(sel);
    show(btnSpa);
    removeSpaFromTargetSelect();
  } else if (language === 'spa') {
    enable(btnSpa);
    addClass(btnSpa, 'select');
    removeClass(btnCat, 'select');
    removeClass(sel, 'select');
    enable(sel);
    show(btnSpa);
    removeSpaFromTargetSelect();
  } else {
    // Third language active on target: move castellà into the select
    sel.value = language;
    removeClass(btnSpa, 'select');
    removeClass(btnCat, 'select');
    addClass(sel, 'select');
    enable(sel);
    hide(btnSpa);
    addSpaToTargetSelect();
  }
}

// ---------------------------------------------------------------------------
// Mobile helpers (unchanged logic, vanilla DOM)
// ---------------------------------------------------------------------------

function setOriginButtonMobile(language: string): void {
  const selMobile = el<HTMLSelectElement>('origin-select-mobil');
  selMobile.value = language;

  const targetMobileOpts = document.querySelectorAll<HTMLOptionElement>(
    '#target-select-mobil option[value="cat"]'
  );
  const dstDropdown = document.querySelector<HTMLElement>(
    'div.btns-llengues-desti .dropdown-menu'
  );

  if (language === 'cat') {
    targetMobileOpts.forEach(o => (o.style.display = ''));
    if (dstDropdown) dstDropdown.style.display = 'none';
  } else {
    targetMobileOpts.forEach(o => (o.style.display = 'none'));
    if (dstDropdown) dstDropdown.style.display = '';
  }
}

function setTargetButtonMobile(language: string): void {
  const selMobile = el<HTMLSelectElement>('target-select-mobil');
  selMobile.value = language;

  const catOpt = selMobile.querySelector<HTMLOptionElement>('option[value="cat"]');
  const dstDropdown = document.querySelector<HTMLElement>(
    'div.btns-llengues-desti .dropdown-menu'
  );

  if (language === 'cat') {
    if (catOpt) catOpt.style.display = '';
    if (dstDropdown) dstDropdown.style.display = 'none';
  } else {
    if (catOpt) catOpt.style.display = 'none';
    if (dstDropdown) dstDropdown.style.display = '';
  }
}

// ---------------------------------------------------------------------------
// Language state setters
// ---------------------------------------------------------------------------

function setOriginLanguage(language: string): void {
  el<HTMLInputElement>('origin_language').value = language;
  neuronalApp.showNeuronal();
}

function setTargetLanguage(language: string): void {
  el<HTMLInputElement>('target_language').value = language;
  neuronalApp.showNeuronal();
}

// ---------------------------------------------------------------------------
// Formes valencianes / mark unknown / autotrad toggles
// ---------------------------------------------------------------------------

function toggleFormesValencianes(status: 'on' | 'off'): void {
  const checkbox = el<HTMLInputElement>('formes_valencianes');
  const label    = el<HTMLElement>('formes_valencianes_label');
  if (status === 'on') {
    enable(checkbox);
    if (label) label.style.color = '#333';
  } else {
    disable(checkbox);
    if (label) label.style.color = '#AAA';
  }
}

function toggleMarkUnknown(status: 'on' | 'off'): void {
  const checkbox = el<HTMLInputElement>('mark_unknown');
  const label    = el<HTMLElement>('mark_unknown_label');
  if (status === 'on') {
    enable(checkbox);
    if (label) label.style.color = '#333';
  } else {
    disable(checkbox);
    if (label) label.style.color = '#AAA';
  }
}

function toggleAutotrad(status: 'on' | 'off'): void {
  const checkbox = el<HTMLInputElement>('auto-trad');
  const label    = el<HTMLElement>('tradueix_online_label');
  if (status === 'on') {
    enable(checkbox);
    if (label) label.style.color = '#333';
  } else {
    disable(checkbox);
    if (label) label.style.color = '#AAA';
  }
}

// ---------------------------------------------------------------------------
// Cookie-backed checkbox helpers
// ---------------------------------------------------------------------------

function setCheckboxValue(cookieKey: string, inputId: string): void {
  const val = getMetaCookie(cookieKey, COOKIE_NAME);
  if (typeof val === 'string' && val !== '') {
    const input = el<HTMLInputElement>(inputId.replace('#', ''));
    if (input) input.checked = val === 'true';
  }
}

function saveCookieFromCheckbox(inputId: string, cookieKey: string): void {
  const input = document.querySelector<HTMLInputElement>(inputId);
  if (input) {
    setMetaCookie(cookieKey, COOKIE_NAME, String(input.checked));
  }
}

// ---------------------------------------------------------------------------
// Translation
// ---------------------------------------------------------------------------

function nl2br(text: string): string {
  return text
    .replace(/\r\n|\n\r|\r|\n/g, '<br />');
}

function updateResult(html: string): void {
  const dst = document.querySelector<HTMLElement>('.second-textarea');
  if (dst) dst.innerHTML = html;
}

function translateText(): void {
  const textarea = document.querySelector<HTMLTextAreaElement>('.primer-textarea');
  if (!textarea || textarea.value.trim() === '') {
    updateResult('');
    return;
  }

  let text = textarea.value;
  if (String.prototype.hasOwnProperty('normalize')) {
    text = text.normalize('NFC');
  }

  const originLanguage  = el<HTMLInputElement>('origin_language').value;
  const targetLanguage  = el<HTMLInputElement>('target_language').value;
  const valencianForms  =
    (document.querySelector<HTMLInputElement>('#formes_valencianes:checked') &&
     originLanguage === 'spa')
      ? '_valencia'
      : '';
  const adaptedTarget   = targetLanguage.replace('cat', 'cat' + valencianForms);
  const langpair        = `${originLanguage}|${adaptedTarget}`;
  const muk             = document.querySelector('#mark_unknown:checked') ? 'yes' : 'no';

  setMetaCookie('source-lang', COOKIE_NAME, originLanguage);
  setMetaCookie('target-lang', COOKIE_NAME, targetLanguage);

  const translateBtn = el<HTMLButtonElement>('translate');
  translateBtn.innerHTML = '<i class="fa fa-spinner fa-pulse fa-fw"></i>';

  const onSuccess = (data: TranslationResponse): void => {
    translateBtn.innerHTML = 'Tradueix';

    if (data.responseStatus === 200) {
      rawText = data.responseData.translatedText;

      const infoText = data.message;
      if (infoText) {
        const msgInfo = el<HTMLElement>('message_info');
        msgInfo.classList.remove('hidden');
        msgInfo.style.display = '';
        el<HTMLElement>('message').innerHTML = infoText;
      }

      const encoded = document.createElement('div');
      encoded.textContent = rawText;
      const encodedText = encoded.innerHTML;

      const translation = nl2br(encodedText);
      const coloured = translation.replace(
        /\*([^.,;:\t<>& ]+)/gi,
        "<span style='background-color: #f6f291'>$1</span>"
      );
      updateResult(coloured);

      if (scrollAfterTranslation) {
        scrollAfterTranslation = false;
        const dst = document.querySelector<HTMLElement>('.second-textarea');
        if (dst && dst.getBoundingClientRect().height < 270) {
          window.scrollTo({ top: dst.offsetTop, behavior: 'smooth' });
        }
      }
    } else {
      onError();
    }
  };

  const onError = (): void => {
    el<HTMLButtonElement>('translate').innerHTML = 'Tradueix';
    el<HTMLElement>('error_title').innerHTML =
      'Sembla que alguna cosa no ha funcionat com calia';
    el<HTMLElement>('error_description').innerHTML =
      "S'ha produït un error en executar la traducció. Proveu una altra vegada ara o més tard. Si el problema persisteix, contacteu amb nosaltres mitjançant el formulari d'ajuda.";
    el<HTMLElement>('error_pagina').click();
  };

  if (neuronalApp.isActive()) {
    fetch(`${neuronal_json_url}/translate/`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ langpair, q: text, savetext: String(shouldSaveText()) }),
    })
      .then(r => r.json())
      .then(onSuccess)
      .catch(onError);
  } else {
    fetch(traductor_json_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        langpair, q: text, markUnknown: muk, key: 'NmQ3NmMyNThmM2JjNWQxMjkxN2N',
      }),
    })
      .then(r => r.json())
      .then(onSuccess)
      .catch(onError);
  }
}

interface TranslationResponse {
  responseStatus: number;
  responseData: { translatedText: string };
  message?: string;
}

function shouldSaveText(): boolean {
  return !!(
    typeof sc_settings !== 'undefined' &&
    sc_settings.log_traductor_source &&
    document.querySelector<HTMLInputElement>('#log_traductor_source:checked')
  );
}

function logSourceText(): void {
  if (!shouldSaveText()) return;

  const textarea = document.querySelector<HTMLTextAreaElement>('.primer-textarea');
  if (!textarea || textarea.value.trim() === '') return;

  const data = {
    source_lang: getMetaCookie('source-lang', COOKIE_NAME),
    source_txt:  textarea.value,
    target_lang: getMetaCookie('target-lang', COOKIE_NAME),
    target_txt:  '',
  };

  fetch('https://www.softcatala.org/api/traductor/feedback/log', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  }).catch(() => { /* fire-and-forget */ });
}

function exchangeTexts(): void {
  const src = document.querySelector<HTMLTextAreaElement>('.primer-textarea');
  const dst = document.querySelector<HTMLElement>('.second-textarea');
  if (!src || !dst) return;
  const originalText    = src.value;
  const translationHtml = dst.innerHTML;
  dst.innerHTML = originalText;
  src.value = translationHtml.replace(/<(?:.|\n)*?>/gm, '');
}

// ---------------------------------------------------------------------------
// File translation
// ---------------------------------------------------------------------------

function validateEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function returnFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} bytes`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1048576).toFixed(1)} MB`;
}

function translateFile(): void {
  const emailInput = el<HTMLInputElement>('n_email');
  const fileInput  = el<HTMLInputElement>('n_file');
  const modelInput = el<HTMLSelectElement>('n_model_name');
  const btn        = el<HTMLButtonElement>('translate_file');

  const displayError = (msg: string): void => {
    btn.innerHTML = 'Demaneu traducció';
    el<HTMLElement>('info').style.display = 'none';
    const errEl = el<HTMLElement>('error');
    errEl.classList.remove('hidden');
    el<HTMLElement>('errormessage').innerHTML = msg;
    errEl.style.display = '';
  };

  const displayOk = (): void => {
    btn.innerHTML = 'Demaneu traducció';
    el<HTMLElement>('error').style.display = 'none';
    const infoEl = el<HTMLElement>('info');
    infoEl.classList.remove('hidden');
    infoEl.style.display = '';
    emailInput.value = '';
    fileInput.value = '';
  };

  if (!validateEmail(emailInput.value)) {
    displayError('Reviseu la vostra adreça electrònica.');
    emailInput.focus();
    return;
  }

  if (!fileInput.files?.[0]) {
    displayError('Cal que trieu un fitxer del vostre ordinador.');
    fileInput.focus();
    return;
  }

  if (fileInput.files[0].size > 8192 * 1024) {
    displayError(`La mida màxima és de 8Mb. El vostre fitxer ocupa ${returnFileSize(fileInput.files[0].size)}.`);
    fileInput.focus();
    return;
  }

  btn.innerHTML = '<i class="fa fa-spinner fa-pulse fa-fw"></i>';

  const formData = new FormData();
  formData.append('email', emailInput.value);
  formData.append('model_name', modelInput.value);
  formData.append('file', fileInput.files[0]);

  fetch(`${neuronal_json_url}/translate_file/`, { method: 'POST', body: formData })
    .then(async r => {
      if (r.ok) {
        displayOk();
      } else {
        const json = await r.json();
        displayError(json['error'] ?? 'Error desconegut');
      }
    })
    .catch(() => displayError("S'ha produït un error inesperat."));
}

// ---------------------------------------------------------------------------
// Clipboard
// ---------------------------------------------------------------------------

function initClipboard(): void {
  const copyBtn = document.getElementById('copy-text');
  if (!copyBtn) return;

  copyBtn.addEventListener('click', () => {
    if (rawText) {
      navigator.clipboard.writeText(rawText).then(
        () => showTooltip("El text s'ha copiat!"),
        () => showTooltip("No s'ha pogut copiar el text :(")
      );
    }
  });
}

function showTooltip(msg: string): void {
  const copyBtn = document.getElementById('copy-text');
  if (!copyBtn) return;
  const tip = document.createElement('span');
  tip.className = 'copy-tooltip';
  tip.textContent = msg;
  tip.style.cssText =
    'position:absolute;background:#333;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;pointer-events:none;margin-left:8px;';
  copyBtn.style.position = 'relative';
  copyBtn.appendChild(tip);
  setTimeout(() => tip.remove(), 2000);
}

// ---------------------------------------------------------------------------
// Neuronal app
// ---------------------------------------------------------------------------

const neuronalApp = (() => {
  const LANGS_BOTH  = ['en', 'fr', 'pt'];
  const LANGS_ONLY  = ['deu', 'ita', 'nld', 'jpn', 'glg', 'oci', 'eus'];
  const LANGS_ALL   = [...LANGS_BOTH, ...LANGS_ONLY];

  function isActive(): boolean {
    const origin = el<HTMLInputElement>('origin_language').value;
    const target = el<HTMLInputElement>('target_language').value;
    const checked = el<HTMLInputElement>('rneuronal')?.checked ?? false;
    return (LANGS_ALL.includes(origin) || LANGS_ALL.includes(target)) && checked;
  }

  function showNeuronalMenu(): void {
    document.querySelectorAll<HTMLElement>('.neuronal').forEach(el => {
      el.classList.remove('hidden');
      el.style.display = '';
    });
    toggleMarkUnknown('off');
    toggleFormesValencianes('off');
    toggleAutotrad('off');
  }

  function hideNeuronalMenu(): void {
    toggleMarkUnknown('on');
    toggleAutotrad('on');
    document.querySelectorAll<HTMLElement>('.neuronal').forEach(el => {
      el.style.display = 'none';
    });
    hide(el('message_info'));
  }

  function showNeuronal(): void {
    const origin = el<HTMLInputElement>('origin_language').value;
    const target = el<HTMLInputElement>('target_language').value;
    const checked = el<HTMLInputElement>('rneuronal')?.checked ?? false;

    if (LANGS_BOTH.includes(origin) || LANGS_BOTH.includes(target)) {
      const rneuronal = el<HTMLInputElement>('rneuronal');
      if (rneuronal) rneuronal.checked = true;
      const panel = el<HTMLElement>('panel-radioneuronal');
      panel.classList.remove('hidden');
      panel.style.display = '';
      if (checked) showNeuronalMenu(); else hideNeuronalMenu();
    } else if (LANGS_ONLY.includes(origin) || LANGS_ONLY.includes(target)) {
      const rneuronal = el<HTMLInputElement>('rneuronal');
      if (rneuronal) rneuronal.checked = true;
      el<HTMLElement>('panel-radioneuronal').classList.add('hidden');
      showNeuronalMenu();
    } else {
      hide(el('panel-radioneuronal'));
      hide(el('info-neuronal'));
      document.querySelectorAll<HTMLElement>('.neuronal').forEach(el => {
        el.style.display = 'none';
      });
      toggleMarkUnknown('on');
      toggleFormesValencianes('on');
      toggleAutotrad('on');
    }
  }

  return { isActive, showNeuronal, showNeuronalMenu, hideNeuronalMenu };
})();

// ---------------------------------------------------------------------------
// Initialisation
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {

  // ── Set initial default language pair (castellà → català) ──────────────
  el<HTMLInputElement>('origin_language').value = 'spa';
  el<HTMLInputElement>('target_language').value = 'cat';

  setOriginButton('spa');
  setTargetButton('cat');
  setOriginButtonMobile('spa');
  setTargetButtonMobile('cat');

  // ── Restore from cookie ─────────────────────────────────────────────────
  setCheckboxValue('auto-trad', '#auto-trad');
  setCheckboxValue('unknown',   '#mark_unknown');
  setCheckboxValue('valencia',  '#formes_valencianes');

  const savedSource = getMetaCookie('source-lang', COOKIE_NAME);
  const savedTarget = getMetaCookie('target-lang', COOKIE_NAME);

  if (savedSource) {
    setOriginLanguage(savedSource);
    setOriginButton(savedSource);
    setOriginButtonMobile(savedSource);
    setTargetLanguage(savedTarget);
    setTargetButton(savedTarget);
    setTargetButtonMobile(savedTarget);
  }

  // ── Checkbox persistence ────────────────────────────────────────────────
  el<HTMLInputElement>('auto-trad')?.addEventListener('change', () =>
    saveCookieFromCheckbox('#auto-trad', 'auto-trad'));
  el<HTMLInputElement>('mark_unknown')?.addEventListener('change', () =>
    saveCookieFromCheckbox('#mark_unknown', 'unknown'));
  el<HTMLInputElement>('formes_valencianes')?.addEventListener('change', () =>
    saveCookieFromCheckbox('#formes_valencianes', 'valencia'));

  // ── Mobile selects ──────────────────────────────────────────────────────
  el<HTMLSelectElement>('origin-select-mobil')?.addEventListener('change', function () {
    const lang = this.value;
    // Invariant: picking any non-cat on origin forces target to cat.
    setOriginLanguage(lang);
    setTargetLanguage('cat');
    setOriginButton(lang);
    setOriginButtonMobile(lang);
    setTargetButton('cat');
    setTargetButtonMobile('cat');
    toggleFormesValencianes(lang === 'spa' ? 'on' : 'off');
  });

  el<HTMLSelectElement>('target-select-mobil')?.addEventListener('change', function () {
    const lang = this.value;
    if (lang === 'spa') {
      setTargetLanguage('spa');
      setTargetButton('spa');
      setTargetButtonMobile('spa');
    } else if (lang === 'cat') {
      setTargetLanguage('cat');
      setTargetButton('cat');
      setTargetButtonMobile('cat');
    } else {
      // Third language on target: force origin to català
      setOriginLanguage('cat');
      setOriginButton('cat');
      setOriginButtonMobile('cat');
      setTargetLanguage(lang);
      setTargetButton(lang);
      setTargetButtonMobile(lang);
      toggleFormesValencianes('off');
    }
  });

  // ── Origin buttons ──────────────────────────────────────────────────────
  el<HTMLButtonElement>('origin-cat').addEventListener('click', () => {
    const prevOrigin = el<HTMLInputElement>('origin_language').value;
    const finalTarget = prevOrigin !== 'cat' ? prevOrigin : 'spa';
    setOriginLanguage('cat');
    setOriginButton('cat');
    setOriginButtonMobile('cat');
    setTargetLanguage(finalTarget);
    setTargetButton(finalTarget);
    setTargetButtonMobile(finalTarget);
    toggleFormesValencianes('off');
  });

  el<HTMLButtonElement>('origin-spa').addEventListener('click', () => {
    setOriginLanguage('spa');
    setTargetLanguage('cat');
    setOriginButton('spa');
    setOriginButtonMobile('spa');
    setTargetButton('cat');
    setTargetButtonMobile('cat');
    toggleFormesValencianes('on');
  });

  el<HTMLSelectElement>('origin-select').addEventListener('change', function () {
    const lang = this.value;
    // Invariant: one side must always be català.
    // Picking any non-cat on origin forces target to cat.
    setOriginLanguage(lang);
    setTargetLanguage('cat');
    setOriginButton(lang);
    setOriginButtonMobile(lang);
    setTargetButton('cat');
    setTargetButtonMobile('cat');
    toggleFormesValencianes(lang === 'spa' ? 'on' : 'off');
  });

  // ── Target buttons ──────────────────────────────────────────────────────
  el<HTMLButtonElement>('target-spa').addEventListener('click', () => {
    setTargetLanguage('spa');
    setTargetButton('spa');
    setTargetButtonMobile('spa');
  });

  el<HTMLButtonElement>('target-cat').addEventListener('click', () => {
    // Invariant: one side must always be català.
    // If origin is already cat, move it to the current target language first.
    const currentOrigin = el<HTMLInputElement>('origin_language').value;
    const currentTarget = el<HTMLInputElement>('target_language').value;
    if (currentOrigin === 'cat') {
      const newOrigin = currentTarget !== 'cat' ? currentTarget : 'spa';
      setOriginLanguage(newOrigin);
      setOriginButton(newOrigin);
      setOriginButtonMobile(newOrigin);
      toggleFormesValencianes(newOrigin === 'spa' ? 'on' : 'off');
    }
    setTargetLanguage('cat');
    setTargetButton('cat');
    setTargetButtonMobile('cat');
  });

  el<HTMLSelectElement>('target-select').addEventListener('change', function () {
    const lang = this.value;
    if (lang === 'spa') {
      // User picked castellà from the select — treat as shortcut button click
      setTargetLanguage('spa');
      setTargetButton('spa');
      setTargetButtonMobile('spa');
    } else if (lang === 'cat') {
      setTargetLanguage('cat');
      setTargetButton('cat');
      setTargetButtonMobile('cat');
    } else {
      // Third language on target: force origin to català
      setOriginLanguage('cat');
      setOriginButton('cat');
      setOriginButtonMobile('cat');
      setTargetLanguage(lang);
      setTargetButton(lang);
      setTargetButtonMobile(lang);
      toggleFormesValencianes('off');
    }
  });

  // ── Direction swap ──────────────────────────────────────────────────────
  document.querySelector<HTMLButtonElement>('.direccio')?.addEventListener('click', () => {
    const newTarget = el<HTMLInputElement>('origin_language').value;
    const newOrigin = el<HTMLInputElement>('target_language').value;

    toggleFormesValencianes(newOrigin === 'spa' ? 'on' : 'off');

    setTargetLanguage(newTarget);
    setOriginLanguage(newOrigin);
    setOriginButton(newOrigin);
    setOriginButtonMobile(newOrigin);
    setTargetButton(newTarget);
    setTargetButtonMobile(newTarget);
    exchangeTexts();
  });

  // ── Translate button ────────────────────────────────────────────────────
  el<HTMLButtonElement>('translate').addEventListener('click', () => {
    scrollAfterTranslation = true;
    translateText();
    logSourceText();
  });

  // ── Neteja ───────────────────────────────────────────────────────────────
  document.getElementById('traductor-neteja')?.addEventListener('click', () => {
    const src = document.querySelector<HTMLTextAreaElement>('.primer-textarea');
    const dst = document.querySelector<HTMLElement>('.second-textarea');
    if (src) src.value = '';
    if (dst) dst.innerHTML = '';
  });

  // ── Mark unknown re-translate ────────────────────────────────────────────
  el<HTMLInputElement>('mark_unknown')?.addEventListener('click', translateText);

  // ── Auto-translate on keyup ──────────────────────────────────────────────
  let timer: ReturnType<typeof setTimeout> | null = null;
  let lastPunct = false;
  const PUNCT_KEYS = [46, 33, 58, 63, 47, 45, 190, 171, 49];

  document.querySelector<HTMLTextAreaElement>('.primer-textarea')
    ?.addEventListener('keyup', (e: KeyboardEvent) => {
      if (lastPunct && e.keyCode === 32 || e.keyCode === 13) return;
      if (timer) clearTimeout(timer);
      const timeout = PUNCT_KEYS.includes(e.keyCode) ? 1000 : 3000;
      lastPunct = PUNCT_KEYS.includes(e.keyCode);
      timer = setTimeout(() => {
        const autoTrad = el<HTMLInputElement>('auto-trad');
        if (autoTrad?.checked && !neuronalApp.isActive()) {
          translateText();
        }
      }, timeout);
    });

  // ── Ctrl+Enter shortcut ──────────────────────────────────────────────────
  document.querySelector<HTMLTextAreaElement>('.primer-textarea')
    ?.addEventListener('keydown', (e: KeyboardEvent) => {
      if (e.ctrlKey && e.keyCode === 13) translateText();
    });

  // ── Focus textarea ───────────────────────────────────────────────────────
  document.querySelector<HTMLTextAreaElement>('.primer-textarea')?.focus();

  // ── Neuronal radio buttons ───────────────────────────────────────────────
  document.querySelectorAll<HTMLInputElement>('input[type=radio][name=rneuronal]')
    .forEach(radio => {
      radio.addEventListener('change', () => {
        if (el<HTMLInputElement>('rneuronal').checked) {
          neuronalApp.showNeuronalMenu();
        } else {
          neuronalApp.hideNeuronalMenu();
        }
      });
    });

  // ── Message / error close buttons ───────────────────────────────────────
  document.querySelector<HTMLButtonElement>('#message_info > button')
    ?.addEventListener('click', () => hide(el('message_info')));
  document.querySelector<HTMLButtonElement>('#error > button')
    ?.addEventListener('click', () => hide(el('error')));
  document.querySelector<HTMLButtonElement>('#info > button')
    ?.addEventListener('click', () => hide(el('info')));

  // ── File translation ─────────────────────────────────────────────────────
  el<HTMLButtonElement>('translate_file')?.addEventListener('click', translateFile);

  // ── Clipboard ────────────────────────────────────────────────────────────
  initClipboard();

  // ── Init neuronal state ──────────────────────────────────────────────────
  neuronalApp.showNeuronal();
});

})(); // end IIFE
