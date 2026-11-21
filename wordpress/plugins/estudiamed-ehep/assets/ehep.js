'use strict';

for (const root of document.querySelectorAll('[data-ehep-url]')) {
  const select = selector => root.querySelector(selector);
  const dashboard = select('.ehep-dashboard');
  const session = select('.ehep-session');
  const status = select('.ehep-status');
  const form = select('.ehep-form');
  let attempt;
  let position = 0;
  let secondsTimer;
  let end = 0;
  let busy = false;

  function element(tag, text, className) {
    const node = document.createElement(tag);
    if (text) node.textContent = text;
    if (className) node.className = className;
    return node;
  }

  async function api(path = '', body, query = {}) {
    const url = new URL(root.dataset.ehepUrl, location.href);
    if (url.searchParams.has('rest_route')) {
      url.searchParams.set('rest_route', url.searchParams.get('rest_route') + path);
    } else {
      url.pathname = url.pathname.replace(/\/$/, '') + path;
    }
    for (const [key, value] of Object.entries(query)) url.searchParams.set(key, value);
    const response = await fetch(url, {
      cache: 'no-store',
      headers: { 'X-WP-Nonce': root.dataset.ehepNonce, 'Content-Type': 'application/json' },
      method: body === undefined ? 'GET' : 'POST',
      body: body === undefined ? undefined : JSON.stringify(body)
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'No se pudo completar la solicitud.');
    return data;
  }

  async function run(action) {
    if (busy) return;
    busy = true;
    status.textContent = 'Cargando…';
    try {
      await action();
      status.textContent = '';
    } catch (error) {
      status.textContent = error.message || 'No se pudo cargar el módulo.';
    } finally {
      busy = false;
    }
  }

  function button(label, action) {
    const node = element('button', label);
    node.type = 'button';
    node.addEventListener('click', action);
    return node;
  }

  const percent = (correct, total) => total ? Math.round(100 * correct / total) + ' %' : '—';
  const mode = name => name === 'mock' ? 'Simulacro EstudiaMed' : 'Práctica educativa';

  async function showDashboard(page = 1) {
    clearInterval(secondsTimer);
    const data = await api('', undefined, { page });
    const subject = form.elements.subject;
    const previous = subject.value;
    subject.replaceChildren(new Option('Todas las especialidades', ''));
    for (const [name, total] of Object.entries(data.subjects)) subject.add(new Option(name + ' (' + total + ')', name));
    if (Object.hasOwn(data.subjects, previous)) subject.value = previous;
    function showCount() {
      const count = subject.value ? data.subjects[subject.value] : Object.values(data.subjects).reduce((a, b) => a + b, 0);
      form.elements.count.max = Math.max(1, Math.min(50, count));
      form.elements.count.value = Math.min(Number(form.elements.count.value) || 1, Number(form.elements.count.max));
      select('.ehep-count').textContent = count + (count === 1 ? ' pregunta educativa disponible.' : ' preguntas educativas disponibles.');
      for (const control of form.querySelectorAll('button')) control.disabled = count === 0;
    }
    subject.onchange = showCount;
    showCount();
    const { stats } = data;
    select('.ehep-stats').replaceChildren(element('p', stats.attempts + ' intentos completados'),
      element('p', stats.total + ' preguntas en intentos completados'),
      element('p', stats.total ? percent(stats.correct, stats.total) + ' de aciertos' : 'Sin resultados todavía.'));
    const revision = select('.ehep-revision');
    revision.replaceChildren();
    const ranked = Object.entries(stats.subjects).sort(([, a], [, b]) => a.correct / a.total - b.correct / b.total);
    if (!ranked.length) revision.append(element('p', 'Completa un intento para obtener recomendaciones.'));
    for (const [name, record] of ranked.slice(0, 3)) {
      const available = Object.hasOwn(data.subjects, name);
      revision.append(element('p', name + ' · ' + record.correct + '/' + record.total + ' aciertos'));
      if (available) revision.append(button('Repasar ' + name, () => {
        subject.value = name;
        showCount();
        subject.focus();
      }));
    }
    const history = select('.ehep-history');
    history.replaceChildren();
    if (!data.history.length) history.append(element('p', 'Todavía no tienes intentos completados.'));
    for (const entry of data.history) {
      const row = element('div', '', 'ehep-history-row');
      const label = new Date(entry.finished * 1000).toLocaleDateString('es-EC') + ' · ' +
        mode(entry.mode) + ' · ' + entry.score + '/' + entry.total;
      row.append(element('span', label), button('Revisar', () => run(() => loadAttempt(entry.id))));
      history.append(row);
    }
    if (page > 1) history.append(button('Anterior', () => run(() => showDashboard(page - 1))));
    if (stats.attempts > page * 10) history.append(button('Siguiente', () => run(() => showDashboard(page + 1))));
    const resume = select('.ehep-resume');
    resume.replaceChildren();
    if (data.active) resume.append(button('Continuar último intento', () => run(() => loadAttempt(data.active))));
    session.hidden = true;
    dashboard.hidden = false;
  }

  function setAttempt(value) {
    attempt = value;
    end = performance.now() + Math.max(0, value.deadline - value.serverTime) * 1000;
  }

  async function loadAttempt(id) {
    setAttempt(await api('/attempts/' + id));
    position = 0;
    renderAttempt();
  }

  async function finish() {
    setAttempt(await api('/attempts/' + attempt.id + '/finish', {}));
    renderAttempt();
  }

  function review(item) {
    const body = element('div', '', 'ehep-feedback');
    body.append(element('p', item.selected === null ? 'Sin respuesta' :
      item.selected === item.key ? 'Respuesta correcta' : 'Respuesta incorrecta'));
    const list = element('ol');
    list.type = 'A';
    item.options.forEach((option, index) => {
      list.append(element('li', (index === item.key ? 'Correcta · ' : '') + option + ' — ' + item.reasons[index]));
    });
    body.append(list, element('p', item.source));
    return body;
  }

  function renderAttempt() {
    clearInterval(secondsTimer);
    dashboard.hidden = true;
    session.hidden = false;
    const heading = element('h3', attempt.finished ? 'Resultados · ' + mode(attempt.mode) : mode(attempt.mode));
    heading.tabIndex = -1;
    session.replaceChildren(heading);
    session.append(button('Volver al panel', () => run(() => showDashboard())));
    if (attempt.finished) {
      session.append(element('p', attempt.summary.correct + ' de ' + attempt.summary.total + ' aciertos · ' +
        percent(attempt.summary.correct, attempt.summary.total)));
      attempt.items.forEach((item, index) => {
        const detail = element('details');
        detail.append(element('summary', 'Pregunta ' + (index + 1) + ' · ' + item.subject),
          element('p', item.stem), review(item));
        session.append(detail);
      });
      heading.focus();
      return;
    }
    if (attempt.deadline) {
      const clock = element('p');
      clock.setAttribute('role', 'timer');
      session.append(clock);
      const tick = () => {
        const left = Math.max(0, Math.ceil((end - performance.now()) / 1000));
        clock.textContent = 'Tiempo restante ' + Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
        if (!left && !busy) {
          clearInterval(secondsTimer);
          run(finish);
        }
      };
      secondsTimer = setInterval(tick, 250);
      tick();
    }
    const item = attempt.items[position];
    session.append(element('p', item.subject + ' · Pregunta ' + (position + 1) + ' de ' + attempt.items.length));
    const answers = element('form');
    const options = element('fieldset');
    options.append(element('legend', item.stem));
    item.options.forEach((option, index) => {
      const label = element('label', '', 'ehep-option');
      const radio = element('input');
      radio.type = 'radio';
      radio.name = 'answer';
      radio.value = index;
      radio.checked = item.selected === index;
      radio.required = true;
      label.append(radio, element('span', option));
      options.append(label);
    });
    const checkedPractice = attempt.mode === 'practice' && item.selected !== null;
    options.disabled = checkedPractice;
    answers.append(options);
    if (!checkedPractice) {
      const save = element('button', 'Guardar respuesta');
      save.type = 'submit';
      answers.append(save);
    }
    answers.addEventListener('submit', event => {
      event.preventDefault();
      const value = new FormData(answers).get('answer');
      if (value === null) return;
      run(async () => {
        setAttempt(await api('/attempts/' + attempt.id + '/answer', { item: position, answer: Number(value) }));
        if (!attempt.finished && attempt.mode === 'mock' && position < attempt.items.length - 1) position++;
        renderAttempt();
      });
    });
    session.append(answers);
    if (checkedPractice) session.append(review(item));
    const actions = element('div', '', 'ehep-actions');
    if (position > 0) actions.append(button('Anterior', () => { if (!busy) { position--; renderAttempt(); } }));
    if (position < attempt.items.length - 1) actions.append(button('Siguiente', () => { if (!busy) { position++; renderAttempt(); } }));
    actions.append(button('Finalizar intento', () => run(finish)));
    session.append(actions);
    heading.focus();
  }

  form.addEventListener('submit', event => {
    event.preventDefault();
    run(async () => {
      const values = new FormData(form);
      setAttempt(await api('/attempts', {
        mode: event.submitter?.value || 'practice', subject: values.get('subject'),
        count: Number(values.get('count')), minutes: Number(values.get('minutes'))
      }));
      position = 0;
      renderAttempt();
    });
  });
  run(() => showDashboard());
}
