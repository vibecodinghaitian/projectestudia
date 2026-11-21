'use strict';

for (const root of document.querySelectorAll('[data-estudia-quiz]')) {
  const pt = root.dataset.lang === 'pt';
  const t = (es, portuguese) => pt ? portuguese : es;
  const catalog = JSON.parse(root.dataset.catalog);

  function node(tag, text) {
    const element = document.createElement(tag);
    if (text) element.textContent = text;
    return element;
  }
  function labeled(label, input) {
    const wrapper = node('label', label + ' ');
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
  form.append(labeled(t('Tipo de contenido', 'Tipo de conteúdo'), type),
    labeled(t('Categoría', 'Categoria'), category), labeled(t('Materia', 'Matéria'), subject),
    labeled(t('Número de preguntas', 'Número de questões'), count), labeled(t('Modo', 'Modo'), mode));
  if (root.dataset.ai === '1') {
    form.append(labeled(t('Usar IA si faltan preguntas', 'Usar IA se faltarem questões'), ai));
  }
  form.append(submit);
  root.append(form, status, output);

  function refresh() {
    subject.replaceChildren();
    for (const name of catalog[category.value] || []) subject.add(new Option(name, name));
    subject.disabled = !subject.options.length;
    submit.disabled = subject.disabled;
  }
  category.addEventListener('change', refresh);
  refresh();

  function correction(item, answer) {
    const result = node('div');
    result.className = 'feedback';
    result.append(node('strong', answer === item.key ? t('Correcta', 'Correta') : t('Incorrecta', 'Incorreta')),
      node('p', t('Respuesta correcta: ', 'Resposta correta: ') + item.options[item.key]),
      node('p', item.explanation));
    return result;
  }

  function render(items, practice) {
    output.replaceChildren();
    const answers = new Map();
    const quiz = node('form');
    items.forEach((item, index) => {
      const fieldset = node('fieldset');
      fieldset.className = 'options';
      fieldset.append(node('legend', (index + 1) + '. ' + item.stem));
      if (item.origin === 'ai') {
        fieldset.append(node('p', t('Generada por IA · sin revisión clínica', 'Gerada por IA · sem revisão clínica')));
      } else if (item.source) {
        fieldset.append(node('p', item.source));
      }
      item.options.forEach((option, value) => {
        const input = node('input');
        input.type = 'radio';
        input.name = 'item' + index;
        input.required = true;
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
    if (!practice) {
      const finish = node('button', t('Calificar examen', 'Corrigir prova'));
      finish.className = 'btn';
      quiz.append(finish);
      quiz.addEventListener('submit', event => {
        event.preventDefault();
        const correct = items.filter((item, index) => answers.get(index) === item.key).length;
        output.replaceChildren(node('h3', correct + '/' + items.length + t(' respuestas correctas', ' respostas corretas')));
        items.forEach((item, index) => {
          const details = node('details');
          details.append(node('summary', item.stem), correction(item, answers.get(index)));
          output.append(details);
        });
      });
    }
    output.append(quiz);
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    submit.disabled = true;
    output.replaceChildren();
    status.textContent = t('Preparando el quiz…', 'Preparando o quiz…');
    try {
      const response = await fetch(root.dataset.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': root.dataset.nonce },
        body: JSON.stringify({ lang: root.dataset.lang, category: category.value, subject: subject.value,
          count: Number(count.value), type: type.value, allow_ai: ai.checked })
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      status.textContent = data.items.length
        ? data.items.length + t(' preguntas disponibles.', ' questões disponíveis.')
        : t('Todavía no hay preguntas para esta combinación.', 'Ainda não há questões para esta combinação.');
      if (data.items.length) render(data.items, mode.value === 'study');
    } catch (error) {
      status.textContent = error.message || t('No se pudo preparar el quiz.', 'Não foi possível preparar o quiz.');
    } finally {
      submit.disabled = subject.disabled;
    }
  });
}
