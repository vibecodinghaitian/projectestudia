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
  let referencedTopic = new URLSearchParams(location.search).get('tema');

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
    const topic = form.elements.topic;
    const favorites = form.elements.favorites;
    function topicsForSelection() {
      const available = favorites.checked ? data.favoriteTopics : data.topics;
      if (subject.value) return available[subject.value] || {};
      const combined = new Map();
      for (const topics of Object.values(available)) {
        for (const [name, total] of Object.entries(topics)) combined.set(name, (combined.get(name) || 0) + total);
      }
      return Object.fromEntries(combined);
    }
    function showCount() {
      const available = topicsForSelection();
      const count = topic.value ? available[topic.value] || 0 : Object.values(available).reduce((a, b) => a + b, 0);
      form.elements.count.max = Math.max(1, Math.min(50, count));
      form.elements.count.value = Math.min(Number(form.elements.count.value) || 1, Number(form.elements.count.max));
      select('.ehep-count').textContent = count + (count === 1 ? ' pregunta educativa disponible.' : ' preguntas educativas disponibles.');
      for (const control of form.querySelectorAll('button')) control.disabled = count === 0;
    }
    function refreshTopics() {
      const previousTopic = referencedTopic || topic.value;
      referencedTopic = null;
      const choices = Object.entries(topicsForSelection());
      topic.replaceChildren(new Option('Todos los temas', ''));
      for (const [name, total] of choices) topic.add(new Option(name + ' (' + total + ')', name));
      if (choices.some(([name]) => name === previousTopic)) topic.value = previousTopic;
      topic.disabled = choices.length === 0;
      showCount();
    }
    subject.onchange = favorites.onchange = refreshTopics;
    topic.onchange = showCount;
    refreshTopics();
    const { stats } = data;
    select('.ehep-stats').replaceChildren(element('p', stats.attempts + ' intentos completados'),
      element('p', stats.total + ' preguntas en intentos completados'),
      element('p', stats.total ? percent(stats.correct, stats.total) + ' de aciertos' : 'Sin resultados todavía.'));
    const revision = select('.ehep-revision');
    revision.replaceChildren();
    const ranked = Object.entries(stats.subjects)
      .filter(([, record]) => record.total >= 3 && record.correct < record.total)
      .sort(([, a], [, b]) => a.correct / a.total - b.correct / b.total);
    if (!ranked.length) revision.append(element('p', 'Practica más preguntas para identificar áreas de repaso.'));
    for (const [name, record] of ranked.slice(0, 3)) {
      const available = Object.hasOwn(data.subjects, name);
      revision.append(element('p', name + ' · ' + record.correct + '/' + record.total + ' aciertos'));
      const resource = data.learning?.[name];
      if (resource) {
        const lesson = element('a', 'Estudiar: ' + resource.label);
        lesson.href = resource.url;
        revision.append(lesson);
      }
      if (available) revision.append(button('Repasar ' + name, () => {
        subject.value = name;
        refreshTopics();
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

  function appendImage(parent, item) {
    if (!item.image) return;
    const image = element('img', '', 'ehep-question-image');
    image.src = item.image;
    image.alt = 'Imagen de referencia de la pregunta';
    image.loading = 'lazy';
    parent.append(image);
  }

  function openTutor(index, item) {
    const dialog = element('dialog', '', 'ehep-tutor');
    const title = element('h3', 'Preguntar a Gemini');
    dialog.setAttribute('aria-label', title.textContent);
    const close = button('Cerrar', () => dialog.close());
    dialog.append(title, close);
    root.append(dialog);
    dialog.addEventListener('close', () => dialog.remove(), { once: true });
    dialog.showModal();

    if (root.dataset.ehepGemini !== '1') {
      dialog.append(element('p', 'El chat requiere una clave de Gemini configurada en el servidor. La corrección guardada está disponible.'));
      return;
    }
    const attemptId = attempt.id;
    const form = element('form');
    const label = element('label', 'Tu pregunta');
    const input = element('textarea');
    input.rows = 3;
    input.maxLength = 800;
    input.required = true;
    label.append(input);
    const suggestions = element('div', '', 'ehep-actions');
    for (const text of ['Explícamelo paso a paso', '¿Por qué mi respuesta es incorrecta?', 'Compara las opciones']) {
      suggestions.append(button(text, () => { input.value = text; input.focus(); }));
    }
    if (root.dataset.ehepEditor === '1') {
      suggestions.append(button('Preparar borrador editorial', () => {
        input.value = 'Redacta un borrador para revisión humana: explica la opción del banco y cada distractor. Indica los datos faltantes, posibles discrepancias y fuentes que deben verificarse antes de publicar.';
        input.focus();
      }));
    }
    const submit = element('button', 'Enviar pregunta');
    submit.type = 'submit';
    const transcript = element('div', '', 'ehep-transcript');
    transcript.setAttribute('aria-live', 'polite');
    if (item.image) {
      dialog.append(element('p', 'La imagen incluida se enviará a Gemini.'));
    } else if (/imagen|figura|radiografía|gráfica/i.test(item.stem)) {
      dialog.append(element('p', 'Gemini no recibirá una imagen para este ítem.'));
    }
    form.append(label, submit);
    dialog.append(suggestions, form, transcript);
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const question = input.value.trim();
      if (!question || submit.disabled) return;
      submit.disabled = true;
      transcript.append(element('strong', question));
      const reply = element('p', 'Consultando a Gemini…');
      transcript.append(reply);
      try {
        const result = await api('/attempts/' + attemptId + '/tutor', { item: index, question });
        reply.textContent = (item.image && !result.imageSent ? 'Gemini no recibió la imagen.\n\n' : '') + result.answer;
        input.value = '';
      } catch (error) {
        reply.textContent = error.message;
      } finally {
        submit.disabled = false;
      }
    });
  }

  function review(item, index) {
    const body = element('div', '', 'ehep-feedback');
    body.append(element('p', item.selected === null ? 'Sin respuesta' :
      item.selected === item.key ? 'Respuesta correcta' : 'Respuesta incorrecta'));
    const list = element('ol');
    list.type = 'A';
    item.options.forEach((option, index) => {
      const label = index === item.key ? 'Correcta · ' : index === item.selected ? 'Tu respuesta · ' : '';
      const reason = item.reasons?.[index];
      list.append(element('li', label + option + (reason ? ' — ' + reason : '')));
    });
    body.append(list);
    const clinical = item.clinical;
    if (!clinical && item.explanation) body.append(element('p', item.explanation));
    if (clinical) {
      body.append(element('p', 'Respuesta del banco CACES · Página ' + clinical.pagina + ' · ' + clinical.caces));
      if (clinical.segunGuia) {
        body.append(element('p', (clinical.estado === 'Corrección editorial' ? 'Clave editorial · ' : 'Respuesta según la guía · ') + clinical.segunGuia));
      }
      body.append(element('p', 'Estado de revisión · ' + clinical.estado));
      body.append(element('p', clinical.explicacion || 'La clave coincide con el PDF de 2023. La explicación clínica está pendiente de verificar.'));
      if (clinical.distractor) body.append(element('p', 'Distractor principal · ' + clinical.distractor));
      if (clinical.url && clinical.url.startsWith('https://')) {
        const reference = element('a', clinical.fuente);
        reference.href = clinical.url;
        reference.target = '_blank';
        reference.rel = 'noopener noreferrer';
        const paragraph = element('p');
        paragraph.append(reference);
        body.append(paragraph);
      }
    }
    body.append(button('Preguntar a Gemini', () => openTutor(index, item)));
    body.append(element('p', item.source));
    return body;
  }

  function favoriteControl(item, index) {
    const id = attempt.id;
    const control = button('', async () => {
      control.disabled = true;
      try {
        const result = await api('/attempts/' + id + '/favorite', { item: index, favorite: !item.favorite });
        item.favorite = result.favorite;
        paint();
      } catch (error) {
        status.textContent = error.message;
      } finally {
        control.disabled = false;
      }
    });
    function paint() {
      control.textContent = item.favorite ? 'Quitar de favoritas' : 'Guardar como favorita';
      control.setAttribute('aria-pressed', String(item.favorite));
    }
    paint();
    return control;
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
      const outcomes = element('dl', '', 'ehep-outcomes');
      for (const [label, value] of [['Correctas', attempt.summary.correct], ['Incorrectas', attempt.summary.incorrect], ['Omitidas', attempt.summary.omitted]]) {
        const metric = element('div');
        metric.append(element('dt', label), element('dd', String(value)));
        outcomes.append(metric);
      }
      session.append(outcomes);
      attempt.items.forEach((item, index) => {
        const detail = element('details');
        const outcome = item.selected === null ? 'Omitida' : item.selected === item.key ? 'Correcta' : 'Incorrecta';
        detail.append(element('summary', 'Pregunta ' + (index + 1) + ' · ' + outcome + ' · ' + item.subject),
          element('p', item.stem));
        appendImage(detail, item);
        detail.append(favoriteControl(item, index), review(item, index));
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
    session.append(element('p', item.subject + ' · ' + item.topic + ' · Pregunta ' + (position + 1) + ' de ' + attempt.items.length));
    const answers = element('form');
    const options = element('fieldset');
    options.append(element('legend', item.stem));
    appendImage(options, item);
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
    session.append(answers, favoriteControl(item, position));
    if (checkedPractice) session.append(review(item, position));
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
        topic: values.get('topic') || '', favorites: form.elements.favorites.checked,
        count: Number(values.get('count')), minutes: Number(values.get('minutes'))
      }));
      position = 0;
      renderAttempt();
    });
  });
  run(() => showDashboard());
}
