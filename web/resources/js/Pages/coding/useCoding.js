export const useCoding = () => {
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    let selectedText = '';
    let selectedRange = null;

    const highlightAndAddTextToCode = async (index, currentId, parentId = null) => {
        let targetCode;
        let isReassigning = false;
        let textObject;

        if (isSafari) {
            selectedRange = storedSelectionRange; // Use the stored range if it matches the text
            if (selectedRange.startOffset === selectedRange.endOffset) {
                flashMessage('There was an error, try another way to code text');
                return;
            }
        }

        if (rightClickedText.value) {
            // Reassigning text to another code
            isReassigning = true;
            const oldCodeId = rightClickedText.value.getAttribute('data-id');
            // Remove text from the old code
            const oldCode = findCodeById(filteredCodes.value, oldCodeId).code;

            if (oldCode) {
                const textId = rightClickedText.value.getAttribute('data-text-id');
                const textIndex = oldCode.text.findIndex((text) => text.id === textId);

                if (textIndex !== -1) {
                    textObject = oldCode.text[textIndex];
                    oldCode.text.splice(textIndex, 1);
                }
            }
        } else if (!selectedText || !selectedRange) {
            return;
        }

        if (parentId) {
            // If parentId is provided, fetch the code based on the currentId
            targetCode = findCodeById(filteredCodes.value, currentId).code;
        } else {
            // If no parentId is provided, it's a main parent, so fetch based on the index
            targetCode = filteredCodes.value[index];
        }

        if (!targetCode) return;

        if (isReassigning) {
            const textIndex = rightClickedText.value.getAttribute('data-text-id');
            const matchingSpans = document.querySelectorAll(
                `span[data-text-id="${textIndex}"]`
            );

            matchingSpans.forEach((span) => {
                // Update the data-id attribute of each matching span to the new code id
                span.setAttribute('data-id', targetCode.id);

                // Update the background color of each matching span
                span.style.backgroundColor = targetCode.color;
            });

            // Add the textObject to the new code's text array
            if (textObject) {
                targetCode.text.push(textObject);
            }

            const { response, error } = await request({
                url: `/projects/${projectId}/sources/${props.source.id}/codes/${targetCode.id}/selections/${textObject.id}/change-code`,
                type: 'post',
                body: {
                    oldCodeId: rightClickedText.value.getAttribute('data-id'),
                    newCodeId: targetCode.id,
                },
            });

            if (error) {
                const message = `Error reassigning text: ${response.data.message}`;
                flashMessage(message, { type: 'error' });
            } else if (!response.data.success) {
                const message = `Failed to reassign text.`;
                flashMessage(message, { type: 'error' });
            }
        } else {
            // Handle text highlighting for new text
            handleTextHighlighting(selectedRange, targetCode, selectedText);
        }
    };
}
