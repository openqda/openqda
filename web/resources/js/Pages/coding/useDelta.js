import Quill from 'quill';
import { changeRGBOpacity } from '../../utils/color/changeRGBOpacity.js';

const Delta = Quill.import('delta');

export const useDelta = () => {
  const createDelta = (segments) => {
    const d = new Delta();

    // we use an index-pointer
    // to keep track of current positions
    let i = 0;

    segments.forEach((segment) => {
      const { index, length, type } = segment;
      // set index to starting point of the next segment
      const diff = index - i;
      if (diff > 0) d.retain(diff);

      if (type === 'selection') {
        const { selection } = segment;
        const selectionTitle = `${selection.title} - ${selection.start}:${selection.start + length} (Right-click to open menu)`;
        const background = changeRGBOpacity(selection.code.color);

        d.retain(length, {
          attributes: {
            background,
            title: selectionTitle,
            class:
              'my-0 py-0 border-b border-t border-transparent hover:border-secondary hover:shadow-xl',
            id: selection.id,
          },
        });
      }
      if (type === 'intersection') {
        const { intersection, refs } = segment;
        const selectionTitle = `${selection.title} - ${intersection.start}:${intersection.start + length} (Right-click to open menu)`;
        const background = changeRGBOpacity(selection.code.color);

        d.retain(length, {
          attributes: {
            background,
            title: selectionTitle,
            class:
              'my-0 py-0 border-b border-t border-transparent hover:border-secondary hover:shadow-xl',
            id: selection.id,
          },
        });
      }

      // point to end of the segment
      i = index + length;
    });
  };
};
