<script setup lang="ts"></script>

<template>
  <div id="toolbar">
    <span class="ql-formats">
      <select class="ql-font" title="Font family">
        <option value="arial" selected>Arial</option>
        <option value="comic-sans">Comic Sans</option>
        <option value="courier-new">Courier New</option>
        <option value="georgia">Georgia</option>
        <option value="helvetica">Helvetica</option>
        <option value="lucida">Lucida</option>
      </select>
      <select class="ql-size" title="Font size">
        <option value="extra-small">xs</option>
        <option value="small">sm</option>
        <option value="medium" selected>md</option>
        <option value="large">lg</option>
      </select>
      <select class="ql-header" title="Font style">
        <option value="1">Heading</option>
        <option value="2">Subheading</option>
        <option value="3" selected>Normal</option>
      </select>
    </span>
    <span class="ql-formats">
      <select class="ql-align" title="Align" />
      <select class="ql-color" title="Text color" />
      <select class="ql-background" title="Background color" />
    </span>
    <span class="ql-formats">
      <button class="ql-bold" title="Bold" />
      <button class="ql-italic" title="Italic" />
      <button class="ql-underline" title="Underline" />
      <button class="ql-strike" title="Strike" />
    </span>
    <span class="ql-formats">
      <button class="ql-list" value="ordered" title="Enumeration" />
      <button class="ql-list" value="bullet" title="List" />
      <button class="ql-indent" value="-1" title="+ Indent" />
      <button class="ql-indent" value="+1" title="- Indent" />
    </span>
    <span class="ql-formats">
      <button class="ql-script" value="super" title="Superscript" />
      <button class="ql-script" value="sub" title="Subscript" />
      <button class="ql-blockquote" title="Blockquote" />
      <button class="ql-code-block" title="Preformatted text" />
      <button class="ql-link" title="Hyperlink" />
      <!--
      <button class="ql-image" />
      <button class="ql-video" />
      -->
    </span>
    <!--
    <span class="ql-formats">
      <button class="ql-formula" />
      <button class="ql-clean" />
    </span>
      -->
    <span class="ql-formats">
      <button class="ql-undo" title="Undo">
        <svg viewBox="0 0 18 18">
          <polygon class="ql-fill ql-stroke" points="6 10 4 12 2 10 6 10" />
          <path
            class="ql-stroke"
            d="M8.09,13.91A4.6,4.6,0,0,0,9,14,5,5,0,1,0,4,9"
          />
        </svg>
      </button>
      <button class="ql-redo" title="Redo">
        <svg viewBox="0 0 18 18">
          <polygon class="ql-fill ql-stroke" points="12 10 14 12 16 10 12 10" />
          <path
            class="ql-stroke"
            d="M9.91,13.91A4.6,4.6,0,0,1,9,14a5,5,0,1,1,5-5"
          />
        </svg>
      </button>
    </span>
      <!--
    <span class="ql-formats">
      <button class="ql-settings" title="Editor Settings">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="size-6"
        >
          <path
            class="ql-stroke"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"
          />
          <path
            class="ql-stroke"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
          />
        </svg>
      </button>
    </span>
    -->
  </div>
</template>

<style scoped></style>
