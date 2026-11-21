'use strict';

for (const root of document.querySelectorAll('[data-estudia-quiz]')) {
  const pt = root.dataset.lang === 'pt';
  const t = (es, portuguese) => pt ? portuguese : es;
  const catalog = JSON.parse(root.dataset.catalog);
  const topicCatalog = JSON.parse(root.dataset.topics || '{}');
  const storageKey = 'estudiamed-quiz-v1-' + root.dataset.profile;
  let saved = { favorites: [], attempts: [] };
  try {
    const previous = JSON.parse(localStorage.getItem(storageKey) || 'null');
    if (Array.isArray(previous?.favorites) && Array.isArray(previous?.attempts)) saved = previous;
  } catch {}
  function save() {
    try {
      localStorage.setItem(storageKey, JSON.stringify(saved));
    } catch {
      storageNotice.textContent = 'El navegador no pudo guardar este cambio.';
    }
  }

  function node(tag, text) {
    const element = document.createElement(tag);
    if (text) element.textContent = text;
    return element;
  }
  function labeled(label, input) {
    const wrapper = node('label', label + ' ');
    if (input.type !== 'radio') input.setAttribute('aria-label', label);
    wrapper.append(input);
    return wrapper;
  }
  function options(values) {
    const input = node('select');
    values.forEach(([name, value]) => input.add(new Option(name, value)));
    return input;
  }
  const form = node('form');
  form.className = 'filters';
  const type = options([[t('Opción múltiple', 'Múltipla escolha'), 'mcq'], [t('Caso clínico', 'Caso clínico'), 'case']]);
  const category = options([[t('Selecciona una categoría', 'Selecione uma categoria'), ''], ...Object.keys(catalog).map(key => [key, key])]);
  const subject = options([]);
  const topic = options([]);
  const favorites = node('input');
  favorites.type = 'checkbox';
  const count = node('input');
  count.type = 'number';
  count.min = '1';
  count.max = '10';
  count.value = '1';
  count.required = true;
  const mode = options([[t('Estudio', 'Estudo'), 'study'], [t('Examen', 'Prova'), 'exam']]);
  const ai = node('input');
  ai.type = 'checkbox';
  const submit = node('button', t('Generar', 'Gerar'));
  submit.className = 'btn';
  const status = node('p');
  status.setAttribute('role', 'status');
  const output = node('div');
  const storageNotice = node('p', t('Los resultados y favoritos de Quiz General se guardan en este navegador.', 'Os resultados e favoritos do Quiz Geral são salvos neste navegador.'));
  const history = node('div');
  form.append(labeled(t('Tipo de contenido', 'Tipo de conteúdo'), type),
    labeled(t('Categoría', 'Categoria'), category), labeled(t('Materia', 'Matéria'), subject),
    labeled(t('Tema', 'Tema'), topic), labeled(t('Número de preguntas', 'Número de questões'), count), labeled(t('Modo', 'Modo'), mode),
    labeled(t('Solo favoritas', 'Apenas favoritas'), favorites));
  if (root.dataset.ai === '1') {
    form.append(labeled(t('Usar IA si faltan preguntas', 'Usar IA se faltarem questões'), ai));
  }
  form.append(submit);
  root.append(form, storageNotice, status, output, history);

  function refresh() {
    subject.replaceChildren();
    for (const name of catalog[category.value] || []) subject.add(new Option(name, name));
    subject.disabled = !subject.options.length;
    submit.disabled = subject.disabled;
    refreshTopics();
  }
  function refreshTopics() {
    const topics = topicCatalog[category.value]?.[subject.value]?.[type.value] || {};
    topic.replaceChildren(new Option(t('Todos los temas', 'Todos os temas'), ''));
    for (const name of Object.keys(topics)) topic.add(new Option(name, name));
    topic.disabled = !Object.keys(topics).length;
  }
  subject.addEventListener('change', refreshTopics);
  type.addEventListener('change', refreshTopics);
  category.addEventListener('change', refresh);
  refresh();

  function correction(item, answer) {
    const result = node('div');
    result.className = 'feedback';
    result.append(node('strong', answer == null ? t('Omitida', 'Omitida') : answer === item.key ? t('Correcta', 'Correta') : t('Incorrecta', 'Incorreta')),
      node('p', t('Respuesta correcta: ', 'Resposta correta: ') + item.options[item.key]),
      node('p', item.explanation));
    if (item.reasons) {
      const list = node('ul');
      item.options.forEach((option, index) => list.append(node('li', option + ' — ' + item.reasons[index])));
      result.append(list);
    }
    return result;
  }

  function paintHistory() {
    history.replaceChildren(node('h3', t('Intentos guardados', 'Sessões salvas')));
    if (!saved.attempts.length) {
      history.append(node('p', t('Todavía no hay intentos.', 'Ainda não há sessões.')));
    }
    for (const record of saved.attempts) {
      const disclosure = node('details');
      disclosure.append(node('summary', new Date(record.date).toLocaleDateString(root.dataset.lang) +
        ' · ' + record.subject + ' · ' + record.correct + '/' + record.items.length));
      record.items.forEach((item, index) => {
        disclosure.append(node('p', item.stem), correction(item, record.answers[index]));
      });
      history.append(disclosure);
    }
  }
  paintHistory();

  function render(items, practice, token) {
    output.replaceChildren();
    const answers = new Map();
    const quiz = node('form');
    items.forEach((item, index) => {
      const fieldset = node('fieldset');
      fieldset.className = 'options';
      fieldset.append(node('legend', (index + 1) + '. ' + item.stem));
      if (item.origin === 'sample') {
        fieldset.append(node('p', t('Muestra original de EstudiaMed', 'Amostra original do EstudiaMed')));
      }
      if (item.id) {
        const star = node('button');
        star.type = 'button';
        const paint = () => {
          const active = saved.favorites.includes(item.id);
          star.textContent = active ? t('Quitar de favoritas', 'Remover dos favoritos') : t('Guardar como favorita', 'Salvar nos favoritos');
          star.setAttribute('aria-pressed', String(active));
        };
        star.addEventListener('click', () => {
          saved.favorites = saved.favorites.includes(item.id)
            ? saved.favorites.filter(id => id !== item.id)
            : [...saved.favorites, item.id].slice(-300);
          save();
          paint();
        });
        paint();
        quiz.append(star);
      }
      if (item.origin === 'ai') {
        fieldset.append(node('p', t('Generada por IA · sin revisión clínica', 'Gerada por IA · sem revisão clínica')));
      } else if (item.source) {
        fieldset.append(node('p', item.source));
      }
      item.options.forEach((option, value) => {
        const input = node('input');
        input.type = 'radio';
        input.name = 'item' + index;
        input.required = false;
        input.addEventListener('change', () => {
          answers.set(index, value);
          if (practice) {
            fieldset.append(correction(item, value));
            fieldset.disabled = true;
          }
        });
        fieldset.append(labeled(option, input));
      });
      quiz.append(fieldset);
    });
    const finish = node('button', practice ? t('Guardar práctica', 'Salvar prática') : t('Entregar examen', 'Entregar prova'));
    finish.className = 'btn';
    quiz.append(finish);
    quiz.addEventListener('submit', async event => {
      event.preventDefault();
      finish.disabled = true;
      const selected = items.map((_, index) => answers.get(index) ?? null);
      let result;
      if (practice) {
        const correct = items.filter((item, index) => answers.get(index) === item.key).length;
        result = { items, correct, omitted: items.length - answers.size, incorrect: answers.size - correct };
      } else {
        try {
          result = await request('/finish', { token, answers: selected });
        } catch (error) {
          status.textContent = error.message;
          finish.disabled = false;
          return;
        }
      }
      const { correct, omitted, incorrect } = result;
      saved.attempts.unshift({ date: Date.now(), subject: subject.value, correct,
        items: result.items, answers: selected });
      saved.attempts = saved.attempts.slice(0, 20);
      save();
      paintHistory();
      output.replaceChildren(node('h3', correct + '/' + items.length + t(' respuestas correctas', ' respostas corretas')),
        node('p', t('Correctas: ', 'Corretas: ') + correct + t(' · Incorrectas: ', ' · Incorretas: ') + incorrect + t(' · Omitidas: ', ' · Omitidas: ') + omitted));
      result.items.forEach((item, index) => {
        const details = node('details');
        details.append(node('summary', item.stem), correction(item, answers.get(index)));
        output.append(details);
      });
    });
    output.append(quiz);
  }

  async function request(path, body) {
    const url = new URL(root.dataset.endpoint, location.href);
    if (url.searchParams.has('rest_route')) {
      url.searchParams.set('rest_route', url.searchParams.get('rest_route') + path);
    } else {
      url.pathname = url.pathname.replace(/\/$/, '') + path;
    }
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': root.dataset.nonce },
      body: JSON.stringify(body)
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message);
    return data;
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    submit.disabled = true;
    const practice = mode.value === 'study';
    output.replaceChildren();
    status.textContent = t('Preparando el quiz…', 'Preparando o quiz…');
    try {
      const data = await request('', { lang: root.dataset.lang, category: category.value, subject: subject.value,
        count: Number(count.value), type: type.value, topic: topic.value, mode: practice ? 'study' : 'exam',
        favorites: favorites.checked ? saved.favorites : null, allow_ai: ai.checked });
      status.textContent = data.items.length
        ? data.items.length + t(' preguntas disponibles.', ' questões disponíveis.')
        : t('Todavía no hay preguntas para esta combinación.', 'Ainda não há questões para esta combinação.');
      if (data.items.length) render(data.items, practice, data.token);
    } catch (error) {
      status.textContent = error.message || t('No se pudo preparar el quiz.', 'Não foi possível preparar o quiz.');
    } finally {
      submit.disabled = subject.disabled;
    }
  });
}
