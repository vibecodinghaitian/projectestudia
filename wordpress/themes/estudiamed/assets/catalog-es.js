(() => {
  const { exams, questions } = window.estudiaCatalog;
  const spanishQuestions = {
    'rev-2023-q01': {
      specialtyName: 'Medicina interna',
      statement: 'Un hombre de 58 años, hipertenso y fumador, acude a urgencias por dolor retroesternal opresivo de dos horas de evolución, irradiado a la mandíbula y al miembro superior izquierdo, con sudoración fría y náuseas. En la exploración: PA 140/90 mmHg, FC 88 lpm, FR 18 rpm y SatO₂ 97 % al aire ambiente. La auscultación cardíaca y pulmonar es normal. El electrocardiograma de 12 derivaciones muestra una elevación del segmento ST de 2,5 mm en V1 a V4. El hospital dispone de un servicio de hemodinámica con un tiempo puerta-balón estimado de 45 minutos. ¿Cuál es la actuación inmediata más adecuada?',
      options: [
        'Iniciar de inmediato la trombólisis con tenecteplasa en urgencias.',
        'Derivar al paciente de inmediato para una coronariografía con angioplastia primaria.',
        'Esperar el resultado de los marcadores de necrosis miocárdica (troponina I) antes de decidir la reperfusión.',
        'Administrar heparina no fraccionada en infusión continua y mantener al paciente en observación en la UCI.',
        'Realizar una prueba de esfuerzo urgente para estratificar el riesgo coronario.'
      ],
      explanation: 'Se trata de un infarto agudo de miocardio con elevación del segmento ST de pared anterior (V1 a V4). La angioplastia primaria es la estrategia de reperfusión de elección si puede realizarse dentro de los 120 minutos desde el primer contacto médico. El objetivo puerta-balón es inferior a 90 minutos y en este caso se estima en 45 minutos. Según el supuesto del ejercicio, la trombólisis se consideraría si el tiempo puerta-balón superara los 120 minutos.'
    },
    'rev-2023-q02': {
      specialtyName: 'Pediatría',
      statement: 'La madre lleva a un lactante de 6 meses a una consulta de rutina en el centro de atención primaria. Nació a término, con un peso de 3.200 g. Actualmente pesa 7.500 g y ha recibido lactancia materna exclusiva. Sostiene la cabeza con firmeza, se sienta sin apoyo por breves períodos, pasa objetos de una mano a otra y balbucea sílabas. Su cartilla está al día según el Calendario Nacional de Vacunación del SUS de Brasil. ¿Qué recomiendan el Ministerio de Salud y la Sociedad Brasileña de Pediatría sobre la alimentación complementaria y la suplementación preventiva?',
      options: [
        'Introducir alimentos complementarios, mantener la lactancia materna y continuar la suplementación preventiva con hierro y vitamina D.',
        'Suspender de inmediato la lactancia materna e introducir fórmula de continuación y purés de fruta licuados.',
        'Retrasar la introducción de alimentos hasta los 9 meses, dado que la ganancia de peso es adecuada.',
        'Introducir jugos naturales colados y purés con sal y miel silvestre.',
        'Suspender el suplemento de hierro si la alimentación incluye carne y vísceras a diario.'
      ],
      explanation: 'A los 6 meses termina el período de lactancia materna exclusiva. Se introducen alimentos complementarios saludables y se mantiene la lactancia hasta los 2 años o más. La suplementación preventiva con hierro elemental y vitamina D continúa según las directrices de la Sociedad Brasileña de Pediatría.'
    },
    'rev-2023-q03': {
      specialtyName: 'Cirugía general',
      statement: 'Un hombre de 26 años acude a urgencias tras chocar frontalmente con un objeto fijo en un accidente de tráfico. Llevaba cinturón de seguridad de tres puntos. Al ingreso: Glasgow 15, vía aérea permeable, murmullo vesicular bilateral sin ruidos sobreañadidos, PA 110/70 mmHg, FC 96 lpm. Abdomen plano, con dolor leve a la palpación profunda del hipocondrio izquierdo y sin signos de irritación peritoneal. La ecografía FAST muestra líquido libre en el espacio esplenorrenal. El paciente permanece hemodinámicamente estable tras la infusión de 500 mL de cristaloides templados. ¿Cuál es el siguiente paso más adecuado?',
      options: [
        'Realizar una laparotomía exploradora urgente para una esplenectomía total inmediata.',
        'Realizar una tomografía computarizada de abdomen y pelvis con contraste intravenoso para clasificar la lesión esplénica.',
        'Realizar un lavado peritoneal diagnóstico con análisis celular y bioquímico.',
        'Dar el alta e indicar reposo y retorno en 48 horas si empeora el dolor.',
        'Realizar una paracentesis diagnóstica guiada por ecografía en urgencias.'
      ],
      explanation: 'En un traumatismo abdominal cerrado con FAST positivo y estabilidad hemodinámica, la tomografía abdominal con contraste permite clasificar las lesiones de órganos sólidos, como el bazo y el hígado, y planificar el tratamiento no quirúrgico. La laparotomía inmediata se reserva para la inestabilidad hemodinámica refractaria.'
    }
  };
  window.estudiaCatalogEs = {
    exams: exams.map(exam => ({ ...exam, country: exam.id === 'mir-es' ? 'España' : exam.country })),
    questions: questions.map(question => {
      const translated = spanishQuestions[question.id];
      if (!translated) return question;
      return {
        ...question,
        ...translated,
        options: question.options.map((option, index) => ({ ...option, text: translated.options[index] }))
      };
    })
  };
})();
