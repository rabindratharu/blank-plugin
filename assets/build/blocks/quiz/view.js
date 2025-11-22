/******/ (() => { // webpackBootstrap
/*!****************************************!*\
  !*** ./assets/src/blocks/quiz/view.js ***!
  \****************************************/
/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * Example:
 *
 * ```js
 * {
 *   "viewScript": "file:./view.js"
 * }
 * ```
 *
 * If you're not making any changes to this file because your project doesn't need any
 * JavaScript running in the front-end, then you should delete this file and remove
 * the `viewScript` property from `block.json`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */
document.addEventListener('DOMContentLoaded', function () {
  const forms = document.querySelectorAll('.quiz-form');
  forms.forEach(form => {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const selectedAnswer = form.querySelector('input[name="quiz_answer"]:checked')?.value;
      if (!selectedAnswer) {
        // Use a more user-friendly approach than alert
        const existingError = form.querySelector('.quiz-error');
        if (existingError) {
          existingError.remove();
        }
        const errorElement = document.createElement('div');
        errorElement.className = 'quiz-error';
        errorElement.textContent = 'Please select an answer.';
        errorElement.style.color = 'red';
        errorElement.style.margin = '10px 0';
        form.prepend(errorElement);
        return;
      }

      // Move variable declarations closer to their usage
      const question = form.getAttribute('data-question');
      const correctAnswer = form.getAttribute('data-correct-answer');
      try {
        // Ensure quizBlockData is properly localized/enqueued
        const {
          restUrl,
          nonce
        } = window.quizBlockData || {};
        if (!restUrl || !nonce) {
          throw new Error('Quiz configuration not loaded properly.');
        }
        const response = await fetch(restUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': nonce
          },
          body: JSON.stringify({
            question,
            answer: selectedAnswer,
            correct_answer: correctAnswer
          })
        });
        if (!response.ok) {
          const error = await response.json();
          throw new Error(error.message || 'Network response was not ok');
        }
        const data = await response.json();
        if (data.success) {
          const resultDiv = document.createElement('div');
          resultDiv.className = 'quiz-result';
          resultDiv.innerHTML = `<p>Your answer: ${data.answer}. ${data.message}</p>`;
          form.replaceWith(resultDiv);
        } else {
          throw new Error(data.message || 'Submission failed');
        }
      } catch (error) {
        // Use a more user-friendly approach than console.error and alert
        const existingError = form.querySelector('.quiz-error');
        if (existingError) {
          existingError.remove();
        }
        const errorElement = document.createElement('div');
        errorElement.className = 'quiz-error';
        errorElement.textContent = `An error occurred: ${error.message}`;
        errorElement.style.color = 'red';
        errorElement.style.margin = '10px 0';
        form.prepend(errorElement);
      }
    });
  });
});
/******/ })()
;
//# sourceMappingURL=view.js.map