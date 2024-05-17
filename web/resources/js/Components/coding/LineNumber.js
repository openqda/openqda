export const updateLineNumbers = async (
  editorId,
  lineNumbersId,
  charCount,
  debug = false
) => {
  const editor = document.getElementById(editorId);
  const lineNumbers = document.getElementById(lineNumbersId);

  lineNumbers.innerHTML = '';
  const userDefinedCharsPerLine = charCount;

  if (!editor || !lineNumbers) return;

  const style = window.getComputedStyle(editor);
  const lineHeight = parseInt(style.lineHeight, 10);

  // Prepare all updates before modifying the DOM
  let lineNumberHtml = '';

  const tempSpan = document.createElement('span');
  tempSpan.style.lineHeight = `${lineHeight}px`;
  tempSpan.style.backgroundColor = 'yellow';
  tempSpan.style.position = 'relative';

  tempSpan.style.zIndex = '100';
  tempSpan.style.fontSize = style.fontSize;
  tempSpan.style.padding = style.padding;
  tempSpan.style.fontFamily = style.fontFamily;
  tempSpan.style.fontWeight = style.fontWeight;
  tempSpan.style.lineHeight = style.lineHeight;
  tempSpan.style.width = style.width;
  tempSpan.style.height = style.height;
  const breakElement = document.createElement('br');
  editor.appendChild(breakElement);
  editor.appendChild(tempSpan);

  let textArr = [...editor.textContent];
  let tempCharCount = 0;
  let lineHeightTemp = tempSpan.offsetHeight;

  let actualCharsPerLine = [];

  for (let i = 0; i < textArr.length; i++) {
    tempSpan.style.backgroundColor = 'yellow';
    const char = textArr[i];
    tempSpan.textContent = tempSpan.textContent + char;
    tempCharCount++;

    let currentHeight = tempSpan.offsetHeight;
    if (debug && currentHeight > lineHeightTemp)
      tempSpan.style.backgroundColor = 'red';
    if (currentHeight > lineHeightTemp || i === textArr.length - 1) {
      actualCharsPerLine.push(tempCharCount - 1);
      lineHeightTemp = currentHeight;
      tempCharCount = 0;
    }

    // Pause for 100ms to give you time to see the change -DEBUG
    if (debug) await new Promise((resolve) => setTimeout(resolve, 15));
  }

  tempSpan.remove();
  breakElement.remove();

  const totalChars = editor.innerText.length;
  const totalContentHeight = editor.scrollHeight;
  const actualLines = Math.ceil(totalContentHeight / lineHeight);
  const customLines = Math.ceil(totalChars / userDefinedCharsPerLine);

  let currentLineNumber = 1;
  let overflowChars = 0;
  let lastLineNumber = 0;

  const padLength = customLines.toString(10).length;

  actualCharsPerLine.forEach((charsInLine, index) => {
    charsInLine -= overflowChars;
    overflowChars = 0;
    let lineNumbersForThisLine = [];

    // If charsInLine is 0, it's a continuation of the previous line
    if (charsInLine === 0) {
      lineNumbersForThisLine.push(lastLineNumber);
    } else {
      while (charsInLine >= userDefinedCharsPerLine) {
        if (lastLineNumber === currentLineNumber) currentLineNumber++;
        lineNumbersForThisLine.push(currentLineNumber++);
        charsInLine -= userDefinedCharsPerLine;
      }

      if (charsInLine > 0) {
        if (lastLineNumber === currentLineNumber) currentLineNumber++;
        lineNumbersForThisLine.push(currentLineNumber);
        overflowChars = userDefinedCharsPerLine - charsInLine;
      }

      // Special case for the last line
      if (index === actualCharsPerLine.length - 1) {
        if (overflowChars > 0) {
          // If there's a little overflow, that should be the last line number.
          if (lastLineNumber === currentLineNumber) currentLineNumber++;
          lineNumbersForThisLine.push(currentLineNumber);
        }
        // If it's just a continuation of the previous line, no new line number is added,
        // which means nothing special needs to be done.
      }
    }

    lastLineNumber = lineNumbersForThisLine[lineNumbersForThisLine.length - 1];
    if (lineNumbersForThisLine.length > 0) {
      const firstNumber = String(lineNumbersForThisLine[0]).padStart(
        padLength,
        ' '
      );
      const lastNumber = String(
        lineNumbersForThisLine[lineNumbersForThisLine.length - 1]
      ).padStart(padLength, ' ');

      if (firstNumber === lastNumber) {
        lineNumberHtml += `<span>${firstNumber}</span><br>`;
      } else {
        lineNumberHtml += `<span class="pre-like">${firstNumber} - ${lastNumber}</span><br>`;
      }
    }
  });

  lineNumbers.innerHTML = lineNumberHtml;

  if (debug) {
    console.log('Actual Chars Per Line:', actualCharsPerLine);
    console.log('Total Characters:', totalChars);
    console.log(
      'Sum of Characters in array:',
      actualCharsPerLine.reduce((partialSum, a) => partialSum + a, 0)
    );
    console.log('Actual Lines:', actualLines);
    console.log('Custom Lines:', customLines);
    console.log('Line Numbers HTML:', lineNumberHtml);
  }
};
