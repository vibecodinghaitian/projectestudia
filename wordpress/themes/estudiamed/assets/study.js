(() => {
  'use strict';
  const spanish = document.documentElement.lang === 'es';
  const t = (portuguese, spanishText) => spanish ? spanishText : portuguese;
  const { exams, questions } = spanish ? window.estudiaCatalogEs : window.estudiaCatalog;
  const find = id => document.getElementById(id);
  const catalog = find('catalogo');
  const specialty = find('especialidade');
  const setup = find('configurar');
  const availability = find('disponibilidade');
  const sessionPanel = find('sessao');
  const metrics = find('metricas');
  const storageKey = 'estudiamed-results-v1';
  let history = [];
  let session;
  let timer;

  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
    if (Array.isArray(saved)) {
      history = saved.filter(entry => entry && Number.isInteger(entry.total) && entry.total > 0 &&
        Number.isInteger(entry.correct) && entry.correct >= 0 && entry.correct <= entry.total);
    }
  } catch {
    // Browsers can disable local storage.
  }

  function element(tag, content, className) {
    const node = document.createElement(tag);
    if (content) node.textContent = content;
    if (className) node.className = className;
    return node;
  }

  for (const exam of exams) {
    const count = questions.filter(question => question.examId === exam.id).length;
    const card = element('article', '', 'card');
    card.append(element('span', exam.country, 'pill'), element('h3', exam.code),
      element('p', count ? count + (count === 1 ? t(' questão disponível', ' pregunta disponible') : t(' questões disponíveis', ' preguntas disponibles')) :
        t('Questões ainda indisponíveis', 'Preguntas aún no disponibles')));
    if (count) {
      const link = element('a', t('Praticar', 'Practicar'));
      link.href = '#praticar';
      card.append(link);
    } else {
      card.append(element('span', t('Em breve', 'Próximamente'), 'pending'));
    }
    catalog.append(card);
  }

  for (const name of new Set(questions.map(question => question.specialtyName))) {
    specialty.add(new Option(name, name));
  }

  function selectedQuestions() {
    return questions.filter(question => !specialty.value || question.specialtyName === specialty.value);
  }

  function showAvailability() {
    const count = selectedQuestions().length;
    availability.textContent = count + (count === 1 ? t(' questão para treino.', ' pregunta para practicar.') : t(' questões para treino.', ' preguntas para practicar.')) +
      t(' O simulado inclui ', ' El simulacro incluye ') + questions.length + t(' questões.', ' preguntas.');
  }

  function showMetrics() {
    const total = history.reduce((sum, entry) => sum + entry.total, 0);
    const correct = history.reduce((sum, entry) => sum + entry.correct, 0);
    const stats = [
      [t('Sessões concluídas', 'Sesiones completadas'), history.length],
      [t('Questões praticadas', 'Preguntas practicadas'), total],
      [t('Taxa de acerto', 'Porcentaje de aciertos'), total ? Math.round(correct / total * 100) + '%' : '—']
    ];
    metrics.replaceChildren(...stats.map(([label, value]) => {
      const card = element('div', '', 'card');
      card.append(element('strong', String(value)), element('span', label));
      return card;
    }));
  }

  function focusPanel(node) {
    node.tabIndex = -1;
    node.focus();
  }

  function showQuestion() {
    const question = session.questions[session.answers.length];
    const form = element('form');
    const title = element('h3', question.statement, 'question-title');
    const progress = element('strong',
      question.specialtyName + t(' · Questão ', ' · Pregunta ') + (session.answers.length + 1) + ' de ' + session.questions.length);
    const top = element('div', '', 'question-head');
    top.append(progress);
    if (session.timed) {
      const clock = element('span');
      clock.id = 'relogio';
      clock.setAttribute('role', 'timer');
      top.append(clock);
    }
    const fieldset = element('fieldset', '', 'options');
    fieldset.append(element('legend', t('Selecione uma alternativa', 'Selecciona una opción'), 'screen-reader-text'));
    for (const option of question.options) {
      const label = element('label');
      const input = element('input');
      input.type = 'radio';
      input.name = 'resposta';
      input.value = option.id;
      input.required = true;
      label.append(input, element('span', option.id + '. ' + option.text));
      fieldset.append(label);
    }
    const submit = element('button', session.timed ? t('Confirmar resposta', 'Confirmar respuesta') : t('Ver correção', 'Ver corrección'), 'btn');
    form.append(fieldset, submit);
    sessionPanel.replaceChildren(top, title, form);
    if (session.timed) updateTimer();
    focusPanel(title);

    form.addEventListener('submit', event => {
      event.preventDefault();
      if (session.timed && Date.now() >= session.deadline) {
        finish(true);
        return;
      }
      const answer = new FormData(form).get('resposta');
      session.answers.push(answer);
      if (session.timed) {
        advance();
        return;
      }
      fieldset.disabled = true;
      submit.remove();
      const feedback = explanation(question, answer);
      const next = element('button',
        session.answers.length === session.questions.length ? 'Ver resultado' : t('Próxima questão', 'Siguiente pregunta'), 'btn');
      next.type = 'button';
      next.addEventListener('click', advance, { once: true });
      sessionPanel.append(feedback, next);
      focusPanel(feedback);
    });
  }

  function explanation(question, answer) {
    const box = element('div', '', 'feedback');
    const label = answer === question.correctAnswer ? t('Resposta correta', 'Respuesta correcta') :
      answer ? t('Resposta incorreta', 'Respuesta incorrecta') : t('Sem resposta', 'Sin respuesta');
    box.append(element('strong', label + t(' · Gabarito ', ' · Opción correcta ') + question.correctAnswer),
      element('p', question.explanation));
    return box;
  }

  function advance() {
    if (session.answers.length === session.questions.length) finish(false);
    else showQuestion();
  }

  function updateTimer() {
    const remaining = Math.max(0, Math.ceil((session.deadline - Date.now()) / 1000));
    if (!remaining) {
      finish(true);
      return;
    }
    const clock = find('relogio');
    if (clock) clock.textContent = t('Tempo restante ', 'Tiempo restante ') +
      Math.floor(remaining / 60) + ':' + String(remaining % 60).padStart(2, '0');
  }

  function finish(expired) {
    clearInterval(timer);
    const { questions: attempted, answers } = session;
    const correct = attempted.filter((question, index) => question.correctAnswer === answers[index]).length;
    history.push({ correct, total: attempted.length });
    let saved = true;
    try {
      localStorage.setItem(storageKey, JSON.stringify(history));
    } catch {
      saved = false;
    }
    showMetrics();
    const title = element('h3', expired ? t('Tempo esgotado', 'Tiempo agotado') : t('Sessão concluída', 'Sesión completada'));
    const summary = element('p', correct + ' de ' + attempted.length + t(' questões corretas.', ' preguntas correctas.'));
    const review = element('details', '', 'result');
    const list = element('ol');
    for (const [index, question] of attempted.entries()) {
      const item = element('li');
      const selected = question.options.find(option => option.id === answers[index]);
      const expected = question.options.find(option => option.id === question.correctAnswer);
      item.append(element('p', question.statement));
      if (selected) item.append(element('p', t('Sua resposta · ', 'Tu respuesta · ') + selected.id + '. ' + selected.text));
      item.append(element('p', t('Resposta esperada · ', 'Respuesta esperada · ') + expected.id + '. ' + expected.text),
        explanation(question, answers[index]));
      list.append(item);
    }
    review.append(element('summary', t('Revisar questões e respostas', 'Revisar preguntas y respuestas')), list);
    const restart = element('button', t('Configurar nova sessão', 'Configurar otra sesión'), 'btn btn-outline');
    restart.type = 'button';
    restart.addEventListener('click', () => {
      sessionPanel.hidden = true;
      setup.hidden = false;
      availability.hidden = false;
      specialty.focus();
    });
    sessionPanel.replaceChildren(title, summary, review, element('div', '', 'actions'));
    sessionPanel.lastElementChild.append(restart);
    if (!saved) sessionPanel.append(element('p', t('Este navegador não permitiu salvar o resultado.', 'Este navegador no permitió guardar el resultado.'), 'muted'));
    focusPanel(title);
  }

  specialty.addEventListener('change', showAvailability);
  setup.addEventListener('submit', event => {
    event.preventDefault();
    const timed = event.submitter?.value === 'simulado';
    const bank = timed ? questions : selectedQuestions();
    if (!bank.length) return;
    session = { timed, questions: bank, answers: [], deadline: Date.now() + bank.length * 120000 };
    setup.hidden = true;
    availability.hidden = true;
    sessionPanel.hidden = false;
    showQuestion();
    if (timed) timer = setInterval(updateTimer, 1000);
  });

  showAvailability();
  showMetrics();
})();
