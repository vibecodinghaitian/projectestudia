'use strict';

for (const root of document.querySelectorAll('[data-estudia-quiz]')) {
  const catalogs = JSON.parse(root.dataset.topics);
  const years = JSON.parse(root.dataset.years || '{}');
  const storageKey = 'estudiamed-quiz-v1-' + root.dataset.profile;
  let saved = { favorites: [], attempts: [] };
  try {
    const previous = JSON.parse(localStorage.getItem(storageKey));
    if (Array.isArray(previous?.favorites) && Array.isArray(previous?.attempts)) saved = previous;
  } catch {}

  let lang = root.dataset.lang;
  try {
    lang = localStorage.getItem(storageKey + '-lang') || lang;
  } catch {}
  if (!['es', 'pt'].includes(lang)) lang = 'es';

  const t = (es, pt) => lang === 'pt' ? pt : es;
  const typeLabels = () => ({
    mcq: t('Opción múltiple', 'Múltipla escolha'), case: 'Caso clínico',
    reasoning: t('Razonamiento clínico', 'Raciocínio clínico'), basic: t('Conocimientos básicos', 'Conhecimentos básicos'),
    application: t('Aplicación clínica', 'Aplicação clínica')
  });
  const make = (tag, text = '') => Object.assign(document.createElement(tag), {textContent: text});
  function button(text, action) {
    const control = make('button', text);
    control.type = 'button';
    control.className = 'btn btn-outline';
    control.addEventListener('click', action);
    return control;
  }
  function label(text, control) {
    const wrapper = make('label', text + ' ');
    if (control.type === 'checkbox') wrapper.className = 'quiz-check';
    if (text && control.type !== 'radio') control.setAttribute('aria-label', text);
    wrapper.append(control);
    return wrapper;
  }
  function choices(select, entries, previous = select.value) {
    select.replaceChildren(...entries.map(([key, value]) => new Option(value, key)));
    if (entries.some(([key]) => key === previous)) select.value = previous;
    select.disabled = !entries.length;
  }
  function number(max, value) {
    const input = make('input');
    Object.assign(input, {type: 'number', min: '1', max: String(max), value: String(value), required: true});
    return input;
  }
  function correction(item, answer) {
    const result = make('div');
    result.className = 'feedback';
    result.append(make('strong', answer == null ? t('Omitida', 'Omitida') : answer === item.key ? t('Correcta', 'Correta') : t('Incorrecta', 'Incorreta')));
    if (answer != null) result.append(make('p', t('Tu respuesta: ', 'Sua resposta: ') + item.options[answer]));
    result.append(make('p', t('Respuesta correcta: ', 'Resposta correta: ') + item.options[item.key]), make('p', item.explanation));
    if (Array.isArray(item.reasons)) {
      const list = make('ul');
      item.options.forEach((option, index) => {
        if (item.reasons[index]) list.append(make('li', option + ' — ' + item.reasons[index]));
      });
      result.append(list);
    }
    if (item.exam) result.append(make('p', [item.exam, item.year, item.official_id].filter(Boolean).join(' · ')));
    if (item.source) result.append(make('p', item.source));
    if (typeof item.url === 'string' && /^https?:\/\//i.test(item.url)) {
      const link = make('a', t('Consultar referencia', 'Consultar referência'));
      link.href = item.url;
      result.append(link);
    }
    return result;
  }
  function request(path, body) {
    const url = new URL(root.dataset.endpoint, location.href);
    if (url.searchParams.has('rest_route')) url.searchParams.set('rest_route', url.searchParams.get('rest_route') + path);
    else url.pathname = url.pathname.replace(/\/$/, '') + path;
    return fetch(url, {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-WP-Nonce': root.dataset.nonce},
      body: JSON.stringify(body)
    }).then(async response => {
      if (!response.ok) throw new Error(t('No se pudo completar la operación. Vuelve a intentarlo.', 'Não foi possível concluir. Tente novamente.'));
      return response.json();
    });
  }
  function persist() {
    try {
      localStorage.setItem(storageKey, JSON.stringify(saved));
    } catch {
      notice.textContent = t('El navegador no pudo guardar los cambios.', 'O navegador não conseguiu salvar as alterações.');
    }
  }

  const language = make('select');
  choices(language, [['es', 'Español'], ['pt', 'Português']], lang);
  const notice = make('p');
  const status = make('p');
  status.setAttribute('role', 'status');
  const configuration = make('div');
  const output = make('div');
  const history = make('div');
  root.append(label('Idioma', language), configuration, notice, status, output, history);
  let current = null;
  let retry = null;
  let starting = false;

  language.addEventListener('change', () => {
    lang = language.value;
    try { localStorage.setItem(storageKey + '-lang', lang); } catch {}
    status.textContent = '';
    retry = null;
    showConfiguration();
  });

  function showConfiguration(initial = {}) {
    root.lang = lang === 'pt' ? 'pt-BR' : 'es';
    notice.textContent = t('Tu historial y tus favoritas se guardan en este navegador.', 'Seu histórico e suas favoritas são salvos neste navegador.');
    const catalog = Object.fromEntries(Object.entries(catalogs[lang] || {}));
    const form = make('form');
    form.className = 'filters quiz-filters';
    const type = make('select');
    const category = make('select');
    const subject = make('select');
    const topic = make('select');
    const mode = make('select');
    const year = make('select');
    const yearLabel = label(t('Año', 'Ano'), year);
    const count = number(100, 1);
    const minutes = number(180, 15);
    const minutesLabel = label(t('Minutos del examen', 'Minutos da prova'), minutes);
    const countLabel = label('', count);
    const availability = make('p');
    availability.setAttribute('aria-live', 'polite');
    const favorites = make('input');
    favorites.type = 'checkbox';
    const submit = make('button', t('Generar quiz', 'Gerar quiz'));
    submit.className = 'btn';
    const titles = typeLabels();
    const types = Object.keys(titles).filter(key =>
      Object.values(catalog).some(subjects => Object.values(subjects).some(kinds => Object.hasOwn(kinds, key))));
    choices(type, types.map(key => [key, titles[key]]), initial.type);
    choices(mode, [['study', t('Estudio', 'Estudo')], ['exam', t('Examen', 'Prova')]], initial.mode);
    const entries = values => values.map(value => [value, value]);
    function refresh() {
      const subjectsFor = values => Object.keys(values || {}).filter(key => values[key][type.value]);
      choices(category, entries(Object.keys(catalog).filter(key => subjectsFor(catalog[key]).length)), category.value || initial.category);
      choices(subject, entries(subjectsFor(catalog[category.value])), subject.value || initial.subject);
      const topics = catalog[category.value]?.[subject.value]?.[type.value] || {};
      choices(topic, [['', t('Todos los temas', 'Todos os temas')], ...entries(Object.keys(topics))], topic.value || initial.topic);
      const topicPool = topic.value ? topics[topic.value] || [] : Object.values(topics).flat();
      const availableYears = [...new Set(topicPool.map(id => years[id]).filter(Boolean))].sort((a, b) => b - a).map(String);
      choices(year, [['', t('Todos los años', 'Todos os anos')], ...entries(availableYears)], year.options.length ? year.value : String(initial.year || ''));
      yearLabel.hidden = !availableYears.length;
      const pool = year.value ? topicPool.filter(id => String(years[id]) === year.value) : topicPool;
      const filter = retry || (favorites.checked ? saved.favorites : null);
      const available = filter ? pool.filter(id => filter.includes(id)).length : pool.length;
      availability.textContent = available + (available === 1 ? t(' disponible con estos filtros.', ' disponível com estes filtros.') : t(' disponibles con estos filtros.', ' disponíveis com estes filtros.'));
      submit.disabled = !available || starting;
      const countText = type.value === 'case' ? 'Número de casos' : t('Número de preguntas', 'Número de questões');
      countLabel.firstChild.textContent = countText + ' ';
      count.setAttribute('aria-label', countText);
      minutesLabel.hidden = minutes.disabled = mode.value !== 'exam';
    }
    form.append(label(t('Tipo de contenido', 'Tipo de conteúdo'), type), label(t('Categoría', 'Categoria'), category),
      label(t('Materia', 'Disciplina'), subject), label(t('Tema específico', 'Tema específico'), topic), label(t('Modo', 'Modo'), mode),
      yearLabel, countLabel, minutesLabel, label(t('Solo favoritas', 'Apenas favoritas'), favorites), submit);
    configuration.replaceChildren(form, availability);
    for (const input of [category, subject, topic, type, mode, favorites, year]) {
      input.addEventListener('change', () => { retry = null; status.textContent = ''; refresh(); });
    }
    refresh();
    configuration.hidden = false;
    language.disabled = false;
    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (current || starting) return;
      const settings = {type: type.value, category: category.value, subject: subject.value, topic: topic.value, mode: mode.value, year: year.value ? Number(year.value) : null};
      const prior = saved.attempts.flatMap(record => record.items.map(item => item.id)).filter(Boolean);
      const wanted = Number(count.value);
      starting = true;
      submit.disabled = language.disabled = true;
      status.textContent = t('Preparando el quiz…', 'Preparando o quiz…');
      try {
        const data = await request('', {...settings, lang, count: wanted, minutes: Number(minutes.value),
          seen: [...new Set(prior)].slice(0, 500), favorites: retry || (favorites.checked ? saved.favorites : null)});
        if (!data.items.length) {
          status.textContent = t('Solicitaste ', 'Você solicitou ') + wanted + t('. Disponibles: ', '. Disponíveis: ') + data.available;
          if (data.available) {
            availability.replaceChildren(button(t('Usar las disponibles', 'Usar as disponíveis'), () => {
              count.value = String(data.available);
              form.requestSubmit();
            }));
          }
          return;
        }
        retry = null;
        start(data, settings);
      } catch (error) {
        status.textContent = error.message;
      } finally {
        starting = false;
        if (!current) {
          language.disabled = false;
          submit.disabled = false;
        }
      }
    });
    paintHistory();
  }

  function paintHistory() {
    history.replaceChildren(make('h3', t('Historial y revisión', 'Histórico e revisão')));
    const records = saved.attempts.filter(record => (record.lang || record.items[0]?.lang || 'es') === lang);
    if (!records.length) {
      history.append(make('p', t('Todavía no hay intentos.', 'Ainda não há sessões.')));
      return;
    }
    const groups = new Map();
    const weakTopics = new Map();
    for (const record of records) {
      const aggregate = groups.get(record.subject) || {total: 0, correct: 0, latest: record};
      aggregate.total += record.items.length;
      aggregate.correct += record.correct;
      groups.set(record.subject, aggregate);
      if (!record.category) continue;
      for (const [index, item] of record.items.entries()) {
        const topic = item.topic || record.topic;
        if (!topic || record.answers[index] == null) continue;
        const id = JSON.stringify([record.category, record.subject, record.type, topic]);
        const stats = weakTopics.get(id) || {topic, record, correct: 0, total: 0};
        stats.total++;
        if (record.answers[index] === item.key) stats.correct++;
        weakTopics.set(id, stats);
      }
    }
    const progress = make('ul');
    for (const [subject, data] of [...groups].sort((a, b) => a[1].correct / a[1].total - b[1].correct / b[1].total)) {
      const row = make('li', subject + ' · ' + Math.round(100 * data.correct / data.total) + '% ');
      if (data.correct < data.total && data.latest.category) row.append(button(t('Repasar materia', 'Revisar disciplina'), () => reopen(data.latest, null, '')));
      progress.append(row);
    }
    history.append(progress);
    const weakest = [...weakTopics.values()].filter(stats => stats.correct < stats.total)
      .sort((a, b) => a.correct / a.total - b.correct / b.total).slice(0, 3);
    if (weakest.length) {
      const topics = make('ul');
      history.append(make('h4', t('Temas para repasar', 'Temas para revisar')), topics);
      for (const stats of weakest) {
        const row = make('li', stats.topic + ' ');
        row.append(button(t('Repasar tema', 'Revisar tema'), () => reopen(stats.record, null, stats.topic)));
        topics.append(row);
      }
    }
    for (const record of records) {
      const details = make('details');
      details.append(make('summary', new Date(record.date).toLocaleDateString(lang) + ' · ' + record.subject + ' · ' + record.correct + '/' + record.items.length));
      if (record.mode) {
        details.append(make('p', [record.topic, typeLabels()[record.type],
          record.mode === 'exam' ? t('Examen', 'Prova') : t('Estudio', 'Estudo'), record.duration + ' s'].filter(Boolean).join(' · ')));
        const incorrect = record.items.filter((item, index) => record.answers[index] != null && record.answers[index] !== item.key);
        const marked = record.items.filter((_, index) => (record.marked || []).includes(index));
        for (const [items, wording] of [[incorrect, t('Rehacer incorrectas', 'Refazer incorretas')], [marked, t('Repasar marcadas', 'Revisar marcadas')]]) {
          const ids = items.map(item => item.id).filter(Boolean);
          if (ids.length) details.append(button(wording, () => reopen(record, ids)));
        }
      }
      for (const [index, item] of record.items.entries()) details.append(make('p', item.stem), correction(item, record.answers[index]));
      history.append(details);
    }
  }

  function reopen(record, ids, topic = record.topic) {
    if (current) return;
    retry = ids;
    output.replaceChildren();
    showConfiguration({...record, topic, mode: 'study'});
    configuration.scrollIntoView({block: 'nearest'});
    if (ids) status.textContent = t('La sesión se limitará a las preguntas seleccionadas para revisión.', 'A sessão será limitada às questões selecionadas para revisão.');
  }

  function start(data, settings) {
    const {items, token} = data;
    const practice = settings.mode === 'study';
    const started = Date.now();
    const expires = performance.now() + (data.seconds || 0) * 1000;
    const answers = items.map(() => null);
    const marked = new Set();
    let index = 0;
    let pending = Promise.resolve();
    let finishing = false;
    let timer;
    current = data;
    configuration.hidden = history.hidden = true;
    status.textContent = '';
    const clock = make('p');
    clock.setAttribute('role', 'timer');
    const question = make('div');
    const navigation = make('div');
    navigation.className = 'actions quiz-navigation';
    const finish = button(practice ? t('Guardar práctica', 'Salvar prática') : t('Entregar examen', 'Entregar prova'), complete);
    finish.className = 'btn';
    output.replaceChildren(clock, question, navigation, finish);

    function paint(focus = false) {
      const item = items[index];
      const selected = answers[index];
      const heading = make('h3', t('Pregunta ', 'Questão ') + (index + 1) + '/' + items.length);
      heading.tabIndex = -1;
      const fieldset = make('fieldset');
      fieldset.className = 'options';
      fieldset.append(make('legend', item.stem));
      fieldset.disabled = (practice && selected != null) || finishing || (!practice && performance.now() >= expires);
      item.options.forEach((option, value) => {
        const input = make('input');
        Object.assign(input, {type: 'radio', name: 'quiz-response', checked: value === selected});
        input.addEventListener('change', () => {
          answers[index] = value;
          if (!practice) {
            const snapshot = [...answers];
            pending = pending.then(() => request('/answers', {token, answers: snapshot})).catch(() => {
              status.textContent = t('No se pudo guardar una respuesta en el servidor.', 'Não foi possível salvar uma resposta no servidor.');
            });
          }
          paint(practice);
          if (!practice) question.querySelector('input:checked')?.focus();
        });
        fieldset.append(label(option, input));
      });
      const mark = button(marked.has(index) ? t('Quitar marca', 'Desmarcar') : t('Marcar para revisión', 'Marcar para revisão'), () => {
        if (marked.has(index)) marked.delete(index);
        else marked.add(index);
        paint(true);
      });
      mark.setAttribute('aria-pressed', String(marked.has(index)));
      const favorite = button(saved.favorites.includes(item.id) ? t('Quitar de favoritas', 'Remover dos favoritos') : t('Guardar como favorita', 'Salvar nos favoritos'), () => {
        saved.favorites = saved.favorites.includes(item.id) ? saved.favorites.filter(id => id !== item.id) : [...saved.favorites, item.id].slice(-500);
        persist();
        paint(true);
      });
      question.replaceChildren(heading);
      if (settings.type === 'case' || item.origin === 'sample') question.append(make('p', settings.type === 'case'
        ? t('Caso ficticio para estudio', 'Caso fictício para estudo') : t('Muestra educativa de EstudiaMed', 'Amostra educativa da EstudiaMed')));
      if (item.difficulty) {
        const labels = {beginner: t('Inicial', 'Inicial'), intermediate: t('Intermedio', 'Intermediário'),
          advanced: t('Avanzado', 'Avançado'), exam: t('Examen universitario', 'Prova universitária')};
        if (labels[item.difficulty]) question.append(make('p', labels[item.difficulty]));
      }
      question.append(fieldset, mark, favorite);
      if (practice && selected != null) question.append(correction(item, selected));
      navigation.replaceChildren(make('p', answers.filter(answer => answer != null).length + '/' + items.length + t(' respondidas', ' respondidas')));
      const add = target => {
        const nav = button(String(target + 1) + (marked.has(target) ? ' ★' : '') + (answers[target] != null ? ' ✓' : ''), () => {
          index = target;
          paint(true);
        });
        nav.setAttribute('aria-label', t('Pregunta ', 'Questão ') + (target + 1) + (answers[target] != null ? t(', respondida', ', respondida') : t(', sin responder', ', sem resposta')) + (marked.has(target) ? t(', marcada', ', marcada') : ''));
        if (index === target) nav.setAttribute('aria-current', 'step');
        navigation.append(nav);
      };
      if (!practice) items.forEach((_, target) => add(target));
      else for (const target of [index - 1, index + 1]) if (target >= 0 && target < items.length) add(target);
      if (focus) heading.focus();
    }

    async function complete() {
      if (finishing) return;
      finishing = finish.disabled = true;
      paint();
      try {
        await pending;
        const correct = practice ? items.filter((item, i) => item.key === answers[i]).length : 0;
        const omitted = answers.filter(answer => answer == null).length;
        const result = practice ? {items, answers, correct, omitted, incorrect: items.length - correct - omitted}
          : await request('/finish', {token, answers});
        clearInterval(timer);
        current = null;
        saved.attempts.unshift({...settings, lang, date: Date.now(), duration: Math.round((Date.now() - started) / 1000),
          items: result.items, answers: result.answers, marked: [...marked], correct: result.correct});
        saved.attempts = saved.attempts.slice(0, Math.max(20, saved.attempts.length - 1));
        persist();
        const resultHeading = make('h3', t('Resultado: ', 'Resultado: ') + Math.round(100 * result.correct / items.length) + '%');
        resultHeading.tabIndex = -1;
        const score = [
          [result.correct, t('correcta', 'correta'), t('correctas', 'corretas')],
          [result.incorrect, t('incorrecta', 'incorreta'), t('incorrectas', 'incorretas')],
          [result.omitted, 'omitida', 'omitidas']
        ].map(([total, single, plural]) => total + ' ' + (total === 1 ? single : plural));
        output.replaceChildren(resultHeading, make('p', score.join(', ') + '.'));
        result.items.forEach((item, i) => {
          const details = make('details');
          details.append(make('summary', item.stem), correction(item, result.answers[i]));
          output.append(details);
        });
        history.hidden = false;
        status.textContent = '';
        showConfiguration(settings);
        resultHeading.focus();
      } catch (error) {
        status.textContent = error.message;
        finishing = finish.disabled = false;
        paint();
      }
    }

    function tick() {
      const seconds = Math.max(0, Math.ceil((expires - performance.now()) / 1000));
      clock.textContent = t('Tiempo restante ', 'Tempo restante ') + Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
      if (!seconds) {
        clearInterval(timer);
        complete();
      }
    }
    paint(true);
    clock.hidden = practice;
    if (!practice) {
      timer = setInterval(tick, 200);
      tick();
    }
  }

  showConfiguration();
}
