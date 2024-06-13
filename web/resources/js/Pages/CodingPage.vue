<template>
  <AppLayout title="Coding" class="overflow-hidden">
    <Head :title="'Coding'" />
    <CodingModal
      :title="selectedCode.title"
      :text="selectedCode.text"
      :show="showModal"
      @close="showModal = false"
      size="sm:max-w-xl sm:w-3/4"
    />

    <div
      v-click-outside="{ callback: handleContextMenuClickOutside }"
      id="contextMenu"
      class="fixed px-1.5 py-2 z-50 bg-black hidden max-h-screen w-64 mt-1 overflow-auto rounded-md shadow-xl overflow-y-scroll"
    >
      <button
        id="deleteCodeBtn"
        class="hidden w-full px-4 py-2 text-sm bg-gray-300 rounded-md hover:bg-white"
        @click="deleteTextFromCodeFromContextMenu"
      >
        Remove this code annotation
      </button>
      <ul>
        <template v-for="(code, index) in filteredCodes" :key="code.id">
          <li
            class="px-4 py-2 my-2 text-sm rounded-md cursor-pointer hover:bg-white selection-none contextMenuOption"
            :style="{
              backgroundColor: changeRGBOpacity(code.color, 1),
            }"
            @click="highlightAndAddTextToCode(index, code.id)"
          >
            {{ code.title }}
          </li>

          <!-- Render children, if any -->
          <ul v-if="code.children && code.children.length > 0" class="pl-6">
            <template
              v-for="(childCode, childIndex) in code.children"
              :key="childCode.id"
            >
              <li
                class="px-4 py-2 my-2 text-sm rounded-md cursor-pointer hover:bg-white selection-none contextMenuOption"
                :style="{
                  backgroundColor: changeRGBOpacity(childCode.color, 1),
                }"
                @click="
                  highlightAndAddTextToCode(childIndex, childCode.id, code.id)
                "
              >
                {{ childCode.title }}
              </li>
              <!-- Render grandchildren, if any -->
              <ul v-if="childCode.children.length > 0" class="pl-6">
                <li
                  v-for="(
                    grandChildCode, grandChildIndex
                  ) in childCode.children"
                  :key="grandChildCode.id"
                  class="px-4 py-2 my-2 text-sm rounded-md cursor-pointer hover:bg-white selection-none contextMenuOption"
                  :style="{
                    backgroundColor: changeRGBOpacity(grandChildCode.color, 1),
                  }"
                  @click="
                    highlightAndAddTextToCode(
                      grandChildIndex,
                      grandChildCode.id,
                      childCode.id
                    )
                  "
                >
                  {{ grandChildCode.title }}
                </li>
              </ul>
            </template>
          </ul>
        </template>
      </ul>
    </div>

    <div class="flex">
      <!-- Left Column -->
      <div class="flex flex-col w-3/4 overflow-y-auto">
        <!-- First Row in Left Column with Centered Text -->
        <div class="flex flex-grow justify-between">
          <!-- Adjust height as needed -->
          <h1 class="my-2 text-lg flex font-bold text-porsche-400">
            <button
              type="button"
              @click="showDocumentSettings = true"
              class="ml-2 text-center gap-x-1.5 rounded-md bg-cerulean-700 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700"
            >
              <Bars3Icon class="h-4 w-4" aria-hidden="true"></Bars3Icon>
            </button>
            <div class="ml-2 flex">{{ source.name }}</div>
          </h1>
          <div
            id="selection-info"
            class="text-center opacity-50 hover:right-0 fixed justify-self-end flex bg-cerulean-700 p-2 mt-1.5 text-sm font-semibold text-white shadow-sm z-50"
          >
            Selection: 0 characters
          </div>
          <SideOverlay
            title="Documents"
            :position="'left'"
            :transparency="leftPanelTransparency"
            :show="showDocumentSettings"
            @close="showDocumentSettings = false"
            v-click-outside="(showDocumentSettings = false)"
          >
            <div class="px-4 py-6 sm:px-6 bg-white">
              <div @click="toggleFileSubMenu">
                <div class="flex justify-between cursor-pointer">
                  <span class="text-gray-900">Switch Document</span>
                  <ChevronDownIcon class="h-5 w-5 mr-1" />
                </div>
                <div v-if="showFilesMenu" class="relative left-0 w-full">
                  <div class="bg-white rounded-md shadow-xs">
                    <a
                      v-for="file in props.sources"
                      :key="file.id"
                      @click="codeThisFile(file)"
                      class="block text-sm px-2 py-2 hover:bg-porsche-400 hover:text-white overflow-hidden overflow-ellipsis cursor-pointer flex align-content-start gap-2"
                    >
                      <span>
                        <DocumentTextIcon
                          v-if="file.type.includes('text')"
                          class="h-4 w-4"
                        />
                      </span>
                      <span>{{ file.name }}</span>
                    </a>
                  </div>
                </div>
              </div>

              <div class="my-2">
                <hr />
              </div>

              <!-- Focus Mode -->
              <div class="flex justify-between my-2">
                <span>Focus mode</span>
                <Button
                  title="Toggle focus mode"
                  color="cerulean"
                  @click="toggleFocusMode"
                  :icon="
                    focusMode ? ArrowsPointingInIcon : ArrowsPointingOutIcon
                  "
                />
              </div>

              <div class="w-full my-2">
                <div class="flex justify-between">
                  <label for="fontSizeSlider" class="">Font Size</label>
                  <span class="text-sm">{{ fontSize }}px</span>
                </div>
                <input
                  type="range"
                  id="fontSizeSlider"
                  min="10"
                  max="40"
                  @mousedown="startSliding"
                  @mouseup="endSliding"
                  v-model="fontSize"
                  @input="updateFontSize"
                  class="w-40 cursor-pointer accent-cerulean-700 w-full my-2"
                />
              </div>
              <div class="flex justify-between">
                <label for="line-height-slider">Line Height</label>
                <span class="text-sm">{{ lineHeight }}</span>
              </div>
              <div class="w-full">
                <input
                  type="range"
                  id="line-height-slider"
                  @mousedown="startSliding"
                  @mouseup="endSliding"
                  min="1"
                  max="2.5"
                  step="0.1"
                  v-model="lineHeight"
                  @input="updateLineHeight($event.target.value)"
                  class="w-40 cursor-pointer accent-cerulean-700 w-full my-2"
                />
              </div>
            </div>
          </SideOverlay>
        </div>
        <!-- Second Row in Left Column -->
        <div class="flex flex-grow">
          <div class="w-full px-2 mt-4 editor-container flex flex-grow">
            <div
              id="editor"
              contenteditable="false"
              @contextmenu.prevent="showContextMenu"
              @dragenter.prevent
              @dragover.prevent
              @drop.prevent="handleDropFromCodesList($event)"
              :style="{
                fontSize: fontSize + 'px',
                lineHeight: lineHeight,
              }"
              class="w-full h-full max-h-full p-0 leading-relaxed border-0 border-black resize"
            ></div>
          </div>
        </div>
      </div>
      <!-- Right Column -->
      <div
        class="w-1/4 fixed right-0 h-screen px-2 hide-scrollbar overflow-y-auto"
        id="dropzone"
        :class="[{ 'top-0': hasScrolled }]"
      >
        <div class="flex items-center justify-between my-2">
          <Headline2>Codes</Headline2>
          <Button
            color="cerulean"
            :icon="Cog6ToothIcon"
            @click="showCodesSettings = true"
            class="ml-2 flex items-center gap-x-1.5 rounded-md bg-cerulean-700 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700"
          />

          <SideOverlay
            title="Codes & Codebooks Settings"
            :position="'right'"
            :show="showCodesSettings"
            @close="showCodesSettings = false"
            v-click-outside="(showCodesSettings = false)"
          >
            <div class="px-4 py-6 sm:px-6 bg-white">
              <span class="text-gray-700 font-semibold"
                >Enable/Disable Codebooks</span
              >
              <div
                v-for="codebook in props.codebooks"
                :key="codebook.id"
                class="flex items-center mb-1 last:mb-0"
              >
                <SwitchGroup as="div" class="inline-flex items-center w-full">
                  <SwitchLabel
                    as="dt"
                    class="flex-grow pr-2 font-medium text-gray-700 sm:w-auto"
                    passive
                  >
                    <span>{{ codebook.name }}</span>
                  </SwitchLabel>
                  <dd class="flex items-center">
                    <!-- Remove flex-auto and justify-center -->
                    <Switch
                      :default-checked="
                        activeCodebook.includes(parseInt(codebook.id))
                      "
                      v-slot="{ checked }"
                      :aria-checked="
                        activeCodebook.includes(parseInt(codebook.id))
                      "
                    >
                      <button
                        :class="checked ? 'bg-cerulean-700' : 'bg-gray-200'"
                        @click="updateActiveCodebook(codebook.id)"
                        class="flex w-8 cursor-pointer rounded-full p-px ring-1 ring-inset ring-gray-900/5 transition-colors duration-200 ease-in-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700"
                      >
                        <span
                          aria-hidden="true"
                          class="h-4 w-4 transform rounded-full bg-white shadow-sm ring-1 ring-gray-900/5 transition duration-200 ease-in-out"
                          :class="checked ? 'translate-x-3.5' : 'translate-x-0'"
                        />
                      </button>
                    </Switch>
                  </dd>
                </SwitchGroup>
              </div>

              <div class="my-2">
                <hr />
              </div>

              <button
                @click="hideAllCodes"
                type="button"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-cerulean-700 px-2.5 py-1.5 my-1 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700 w-full"
              >
                <eye-slash-icon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
                Hide Codes
              </button>
              <button
                @click="showAllCodes"
                type="button"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-cerulean-700 px-2.5 py-1.5 my-1 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700 w-full"
              >
                <rectangle-stack-icon
                  class="-ml-0.5 h-5 w-5"
                  aria-hidden="true"
                />
                Show All Codes
              </button>

              <button
                @click="toggleAllCodesText"
                type="button"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-cerulean-700 px-2.5 py-1.5 my-1 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700 w-full"
              >
                <eye-slash-icon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
                Toggle all text in codes
              </button>

              <div class="flex justify-between my-2">
                <span class="font-medium text-gray-700"
                  >Background Opacity</span
                >

                <span class="isolate inline-flex rounded-md shadow-sm">
                  <button
                    @click="toggleOrResetOpacity(0.25)"
                    type="button"
                    :class="
                      globalBackgroundOpacity === 0.25
                        ? 'bg-cerulean-700 text-white'
                        : 'bg-white text-gray-900'
                    "
                    class="relative inline-flex items-center rounded-l-md px-3 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:text-white hover:bg-cerulean-700 focus:z-10"
                  >
                    25%
                  </button>
                  <button
                    @click="toggleOrResetOpacity(0.5)"
                    type="button"
                    :class="
                      globalBackgroundOpacity === 0.5
                        ? 'bg-cerulean-700 text-white'
                        : 'bg-white text-gray-900'
                    "
                    class="relative -ml-px inline-flex items-center px-3 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:text-white hover:bg-cerulean-700 focus:z-10"
                  >
                    50%
                  </button>
                  <button
                    @click="toggleOrResetOpacity(0.75)"
                    type="button"
                    :class="
                      globalBackgroundOpacity === 0.75
                        ? 'bg-cerulean-700 text-white'
                        : 'bg-white text-gray-900'
                    "
                    class="relative -ml-px inline-flex items-center px-3 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:text-white hover:bg-cerulean-700 focus:z-10"
                  >
                    75%
                  </button>
                  <button
                    @click="toggleOrResetOpacity(1.0)"
                    type="button"
                    :class="
                      globalBackgroundOpacity === 1.0
                        ? 'bg-cerulean-700 text-white'
                        : 'bg-white text-gray-900'
                    "
                    class="relative -ml-px inline-flex items-center rounded-r-md px-3 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:text-white hover:bg-cerulean-700 focus:z-10"
                  >
                    100%
                  </button>
                </span>
              </div>

              <!-- Code dimming -->
              <SwitchGroup
                as="div"
                class="inline-flex items-center w-full my-2"
              >
                <SwitchLabel
                  as="dt"
                  class="flex-grow pr-2 font-medium text-gray-700 sm:w-auto"
                  passive
                >
                  <span>Code dimming</span>
                </SwitchLabel>
                <dd class="flex items-center">
                  <Switch
                    v-model="consultCodes"
                    :class="consultCodes ? 'bg-cerulean-700' : 'bg-gray-200'"
                    class="flex w-8 cursor-pointer rounded-full p-px ring-1 ring-inset ring-gray-900/5 transition-colors duration-200 ease-in-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700"
                  >
                    <span
                      aria-hidden="true"
                      :class="
                        consultCodes ? 'translate-x-3.5' : 'translate-x-0'
                      "
                      class="h-4 w-4 transform rounded-full bg-white shadow-sm ring-1 ring-gray-900/5 transition duration-200 ease-in-out"
                    />
                  </Switch>
                </dd>
              </SwitchGroup>
            </div>
          </SideOverlay>
        </div>

        <div class="top-0 z-10 bg-white">
          <div v-if="props.codebooks.length === 0">
            <span>Create a new Codebook to start coding</span>
            <NewCodebookForm
              :project="projectId"
              @codebookCreated="onCodebookCreated"
            />
          </div>
          <div v-else>
            <button
              @click="addCodeToList"
              type="button"
              :disabled="
                activeCodebook.length > 1 || activeCodebook.length === 0
              "
              class="inline-flex items-center gap-x-1.5 rounded-md bg-cerulean-700 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700 w-full mt-2"
              :class="{
                'bg-gray-400 hover:bg-gray-400':
                  activeCodebook.length > 1 || activeCodebook.length === 0,
              }"
            >
              <PlusIcon
                v-show="activeCodebook.length === 1"
                class="-ml-0.5 h-5 w-5"
                aria-hidden="true"
              />
              <div
                v-if="activeCodebook.length > 1 || activeCodebook.length === 0"
                class="w-full flex justify-between"
              >
                <div>To add a main code, enable exactly one codebook</div>
                <div class="mx-1">↑</div>
              </div>
              <span v-else>Add Code</span>
            </button>
          </div>
          <div class="flex items-center gap-x-1.5 pb-1.5 mt-2 rounded-md">
            <input
              v-model="searchQuery"
              placeholder="Search for code title..."
              type="text"
              name="searchQuery"
              id="searchQuery"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
            />
          </div>
        </div>
        <div class="flex flex-col min-h-screen">
          <div class="flex-grow overflow-auto">
            <ul class="py-1 list-none pb-40">
              <li
                class="my-2 text-2xl text-center"
                v-if="filteredCodes.length === 0 && searchQuery !== ''"
              >
                No Result
              </li>

              <template
                v-for="(codes, codebookId) in groupedCodes"
                :key="codebookId"
              >
                <div
                  class="flex flex-col text-white select-none mb-2 mr-2 p-2 rounded font-bold :focus:outline-none :focus:ring-2 :focus:ring-sky-500 :focus:border-sky-500 w-full bg-cerulean-700"
                  @click="toggleCodebookDescription(codebookId)"
                >
                  <div class="flex justify-between items-center">
                    <p class="text-lg flex-grow text-center">
                      {{ codebookDetails.get(parseInt(codebookId)).name }}
                    </p>
                    <div>
                      <ChevronUpIcon
                        v-if="
                          codebookDetails.get(parseInt(codebookId))
                            .showDescription
                        "
                        class="-ml-0.5 h-5 w-5"
                        aria-hidden="true"
                      ></ChevronUpIcon>
                      <ChevronDownIcon
                        v-else-if="
                          codebookDetails.get(parseInt(codebookId)).description
                            .length > 0
                        "
                        class="-ml-0.5 h-5 w-5"
                        aria-hidden="true"
                      ></ChevronDownIcon>
                    </div>
                  </div>
                  <Collapse>
                    <div
                      v-show="
                        codebookDetails.get(parseInt(codebookId)).description
                          .length > 0 &&
                        codebookDetails.get(parseInt(codebookId))
                          .showDescription
                      "
                      class="px-2 py-1 mt-1 text-xs font-normal antialiased text-white whitespace-pre-line text-center"
                    >
                      {{
                        codebookDetails.get(parseInt(codebookId)).description
                      }}
                    </div>
                  </Collapse>
                </div>

                <template v-for="code in codes" :key="code.id">
                  <li
                    @mouseover="lowerOpacityOfOthers(code.id)"
                    @mouseout="resetOpacityOfOthers(code.id)"
                    :draggable="!code.editable[index]"
                    @dragstart="
                      handleDragCodeStart($event, index, null, code.id)
                    "
                    @blur="
                      (event) => {
                        event.stopPropagation();
                      }
                    "
                    ref="codeItems"
                    :style="{ borderColor: code.color }"
                    :class="`select-none mb-2 mr-2 border rounded text-black w-full ${
                      code.editable ? 'border-2 border-orange-500 p-0' : ''
                    }`"
                    :data-id="code.id"
                  >
                    <div
                      class="flex items-center justify-between space-x-4 p-2"
                      :style="{ backgroundColor: code.color }"
                    >
                      <CodeLabel
                        @click.stop
                        :editable="code.editable"
                        @startedit="code.editable = true"
                        @endedit="saveCodeTitle(code.id, $event)"
                        :label="code.title"
                        @changed="saveCodeTitle(code.id, $event)"
                      />
                      <div
                        class="relative inline-flex group py-1 px-2"
                        @click.stop
                        v-click-outside="{
                          callback: handleCodeDescriptionClickOutside,
                        }"
                      >
                        <div class="flex space-x-4">
                          <ChevronUpIcon
                            v-if="code.showDescription"
                            class="-ml-0.5 h-5 w-5"
                            aria-hidden="true"
                            @click="
                              code.showDescription = !code.showDescription
                            "
                          ></ChevronUpIcon>
                          <ChevronDownIcon
                            v-else-if="code.description.length > 0"
                            class="-ml-0.5 h-5 w-5"
                            aria-hidden="true"
                            @click="
                              code.showDescription = !code.showDescription
                            "
                          ></ChevronDownIcon>
                          <PencilSquareIcon
                            @click.stop="openCodeDescription(code)"
                            @mouseover="code.showHoverDescription = true"
                            @mouseleave="code.showHoverDescription = false"
                            class="w-5 h-5 text-black group/item"
                          />
                        </div>

                        <textarea
                          @change="saveDescription(code)"
                          v-if="code.showEditDescription"
                          v-model="code.description"
                          class="absolute w-64 h-64 post-it right-full"
                          rows="4"
                          cols="1"
                        >
                        </textarea>

                        <div
                          v-if="
                            !code.showEditDescription &&
                            code.description.length > 0 &&
                            code.showHoverDescription
                          "
                          class="absolute z-10 px-2 py-1 text-xs text-white whitespace-pre-line bg-gray-700 rounded -bottom-6 right-6"
                        >
                          {{ code.description }}
                        </div>
                      </div>

                      <div class="relative flex items-center">
                        <button
                          @click="toggleCodeText(code.id)"
                          class="text-xs leading-relaxed bg-neutral-100 rounded px-1.5"
                          :class="
                            code.text.length === 0
                              ? 'text-silver-300'
                              : 'cursor-pointer text-silver-900'
                          "
                          :disabled="code.text.length === 0"
                          :title="`${code.text.length} selections for this code`"
                        >
                          {{ code.text.length }}
                        </button>
                        <button
                          @click="
                            code.dropdownOpen = !code.dropdownOpen;
                            code.justOpened = true;
                          "
                          class="z-0 ml-2"
                        >
                          <EllipsisVerticalIcon class="w-5 h-5 text-black" />
                        </button>
                        <DropdownMenu
                          v-if="code.dropdownOpen"
                          v-click-outside="{
                            callback: handleDropdownClickOutside,
                          }"
                          :index="index"
                          :code="code"
                          :codes="codes"
                          :level="0"
                        />
                      </div>
                    </div>
                    <Collapse>
                      <div
                        v-if="
                          code.description.length > 0 && code.showDescription
                        "
                        :style="{ backgroundColor: code.color }"
                        class="px-2 py-1 text-xs antialiased whitespace-pre-line"
                      >
                        {{ code.description }}
                      </div>
                    </Collapse>
                    <transition
                      name="accordion"
                      enter-active-class="transition duration-200 ease-out"
                      enter-from-class="translate-y-1 opacity-0"
                      enter-to-class="translate-y-0 opacity-100"
                      leave-active-class="transition duration-150 ease-in"
                      leave-from-class="translate-y-0 opacity-100"
                      leave-to-class="translate-y-1 opacity-0"
                    >
                      <div
                        v-if="code.showText"
                        class="text-sm divide-y divide-silver-300 cursor-text"
                        key="code.text"
                      >
                        <div
                          v-for="(item, textIndex) in code.text"
                          :key="textIndex"
                          class=""
                        >
                          <div
                            class="h-auto text-xs flex bg-gray-300 border border-solid border-1 border-b-silver-300 p-1 font-mono"
                          >
                            <div
                              class="p-0 cursor-pointer"
                              @click="
                                scrollToTextPosition(item.start, item.end)
                              "
                            >
                              {{ item.start }} -
                              {{ item.end }}
                            </div>
                            <button
                              class="flex ml-auto cursor-pointer"
                              @click="
                                deleteTextFromCode(item, textIndex, code.id)
                              "
                            >
                              <XCircleSolidIcon
                                class="w-4 h-4 text-silver-900 hover:text-silver-500"
                              ></XCircleSolidIcon>
                            </button>
                          </div>
                          <div class="p-2">
                            {{ item.text }}
                          </div>
                          <!-- Don't render <hr> after last item -->
                        </div>
                      </div>
                    </transition>
                  </li>
                  <!-- Recursive part: render children -->
                  <template v-if="code.children.length > 0">
                    <CodeItem
                      v-for="(childCode, childIndex) in code.children"
                      ref="codeItemComponents"
                      :key="childCode.id"
                      :code="childCode"
                      :index="childIndex"
                      :parentId="code.id"
                      :level="1"
                      @child-drag="handleDragCodeStart"
                    />
                  </template>
                </template>
              </template>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
/* global editor */
import {
  computed,
  onBeforeUnmount,
  onMounted,
  provide,
  reactive,
  ref,
  watch,
  watchEffect,
} from 'vue';
import CodingModal from '../Components/coding/CodingModal.vue';
import CodeItem from '../Components/coding/CodeItem.vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue';
import {
  closeFullscreen,
  openFullscreen,
} from '../Components/coding/FullScreen.js';
import { XCircleIcon as XCircleSolidIcon } from '@heroicons/vue/24/solid';
import { vClickOutside } from '../Components/coding/clickOutsideDirective.js';
import DropdownMenu from '../Components/coding/DropdownMenu.vue';
import {
  ArrowsPointingInIcon,
  ArrowsPointingOutIcon,
  Bars3Icon,
  ChevronDownIcon,
  ChevronUpIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  EllipsisVerticalIcon,
  EyeSlashIcon,
  PencilSquareIcon,
  PlusIcon,
  RectangleStackIcon,
} from '@heroicons/vue/24/outline';
import CodeLabel from '../Components/coding/CodeLabel.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import NewCodebookForm from '../Components/project/NewCodebookForm.vue';
import SideOverlay from '../Components/layout/SideOverlay.vue';
import Button from '../Components/interactive/Button.vue';
import Headline2 from '../Components/layout/Headline2.vue';
import Collapse from '../Components/layout/Collapse.vue';

// Define a prop for the HTML content
const props = defineProps(['source', 'sources', 'codebooks', 'allCodes']);

let codes = ref([]);
let codeItems = ref([]);
let childCodeItems = ref([]);
let codeItemComponents = ref([]);

let activeCodebook = ref([]);

// Create a reactive map to maintain a relationship between code id and its corresponding DOM element
let codeIdToElementMap = reactive(new Map());
let selectedText = '';
let selectedRange = null;
let showModal = ref(false);
let showDocumentSettings = ref(false);
let showCodesSettings = ref(false);
let selectedCode = ref({ title: '', text: [] });
let focusMode = ref(false); // For focus mode toggle
let consultCodes = ref(false); // For consult codes toggle
const isFullscreen = ref(false);
const searchQuery = ref('');
const url = window.location.pathname;
const segments = url.split('/');
const projectId = segments[2]; // Assuming project id is the third segment in URL path
let showFilesMenu = ref(false);
const hasScrolled = ref(false); // Declare reactive flag

let rightClickedText = ref();
let opacityForNewColors = 0.5;
const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

const leftPanelTransparency = ref(false);

const startSliding = () => (leftPanelTransparency.value = true);
const endSliding = () => (leftPanelTransparency.value = false);

// Reactive state for font size
const fontSize = ref(16); // Default font size
const lineHeight = ref(1.5); // Initial line-height value

// Method to update font size and save to localStorage
const updateFontSize = () => {
  localStorage.setItem('editorFontSize', fontSize.value);
};

const toggleCodebookDescription = (codebookId) => {
  const details = codebookDetails.value.get(parseInt(codebookId, 10));
  if (details) {
    // Check if the description is not empty or null
    if (details.description && details.description.trim() !== '') {
      details.showDescription = !details.showDescription;
      // Directly mutating the Map should work with Vue 3's reactivity system
      codebookDetails.value.set(codebookId, details);
    }
  }
};

const updateActiveCodebook = (codebookId) => {
  const index = activeCodebook.value.indexOf(codebookId);

  if (index === -1) {
    // Add the codebook ID if it's not already in the array
    activeCodebook.value.push(codebookId);
  } else {
    // Remove the codebook ID if it's already in the array
    activeCodebook.value.splice(index, 1);
  }

  // Update localStorage
  localStorage.setItem('codebook', JSON.stringify(activeCodebook.value));
  editor.innerHTML = props.source.content;

  highlightStoredTextsFromDB(editor);
};

// Load activeCodebook from localStorage when the component is mounted
const loadActiveCodebookFromLocalStorage = () => {
  const storedCodebook = localStorage.getItem('codebook');
  if (storedCodebook) {
    try {
      const parsedCodebook = JSON.parse(storedCodebook);
      if (Array.isArray(parsedCodebook)) {
        activeCodebook.value = parsedCodebook;
      }
    } catch (e) {
      console.error('Error parsing codebook from localStorage', e);
    }
  }
};
loadActiveCodebookFromLocalStorage();

/**
 * group codes by codebook
 * @type {ComputedRef<*>}
 */
const groupedCodes = computed(() => {
  const filteredCodebooks = [];
  for (let [id, cb] of codebookDetails.value) {
    if (activeCodebook.value.includes(id)) {
      cb.id = id;
      filteredCodebooks.push(cb);
    }
  }

  return filteredCodebooks.reduce((grouped, cb) => {
    // Check if there are codes for the current codebook in filteredCodesArray
    const filteredCodesForCb = [];
    for (let fcode of filteredCodes.value) {
      if (fcode.codebook === cb.id) {
        filteredCodesForCb.push(fcode);
      }
    }
    grouped[cb.id] = (grouped[cb.id] || []).concat(filteredCodesForCb);

    return grouped;
  }, {});
});

// Function to update line height in local storage and apply it to the editor
function updateLineHeight(newValue) {
  lineHeight.value = newValue;
  localStorage.setItem('editorLineHeight', newValue);
  // Apply the new line height to the editor
  document.getElementById('editor').style.lineHeight = newValue;
}

const openCodeDescription = (code) => {
  code.showEditDescription = !code.showEditDescription;
  code.editable = !code.showEditDescription;
};

const deleteTextFromCodeById = (codeId, textIndex, codesArray) => {
  let codeObject = findCodeById(codesArray, codeId);
  if (codeObject.code) {
    codeObject.code.text.splice(textIndex, 1);
    return true;
  }
  return false;
};

function calculateSelectionCharPositions(editor) {
  const selection = window.getSelection();
  if (!selection.rangeCount) return { start: 0, end: 0 };

  const range = storedSelectionRange; // Assuming storedSelectionRange is globally accessible
  let start = 0;
  let end = 0;
  let charCount = 0;
  const walker = document.createTreeWalker(
    editor,
    NodeFilter.SHOW_TEXT,
    null,
    false
  );
  let node,
    foundStart = false;

  while ((node = walker.nextNode())) {
    if (node === range.startContainer) {
      start = charCount + range.startOffset;
      foundStart = true;
      if (node === range.endContainer) {
        end = start + (range.endOffset - range.startOffset);
        break;
      }
    } else if (foundStart) {
      if (node === range.endContainer) {
        end = start + range.endOffset;
        break;
      }
      start += node.textContent.length;
    }
    if (!foundStart) charCount += node.textContent.length;
  }

  return { start, end };
}

/**
 * Adjust the position of the selection info div based on the viewport width
 * to keep it visible and not hidden behind the right column
 * @returns {void}
 */
function adjustSelectionInfoPosition() {
  const selectionInfo = document.getElementById('selection-info');
  const viewportWidth = window.innerWidth;
  const offsetRight = viewportWidth * 0.25; // Adjust based on your layout
  selectionInfo.style.right = `${offsetRight}px`;
}

/**
 * Update the selection info div with the current selection range
 * @param start
 * @param end
 */
function updateSelectionInfo(start, end) {
  const infoDiv = document.getElementById('selection-info');
  if (start !== end) {
    // Ensure there is a selection
    infoDiv.classList.remove('opacity-50');
    infoDiv.textContent = `Selection: characters ${start} to ${end}`;
  } else {
    infoDiv.classList.add('opacity-50');
    infoDiv.textContent = 'Selection: 0 characters';
  }
}

const deleteTextFromCode = (item, textIndex, codeId) => {
  const isConfirmed = window.confirm(
    'Are you sure you want to delete this text from the code?'
  );
  if (!isConfirmed) return;

  let noSpans = false;

  // Step 1: Query all span elements by their data-text-index attribute
  const spanElements = document.querySelectorAll(`[data-text-id="${item.id}"]`);

  if (spanElements.length === 0) {
    console.warn('No spans found');
    noSpans = true;
  }

  // Step 3: Loop through all span elements and replace their outerHTML with their innerHTML
  // Keep formatting here when deleting <br> problem?
  if (!noSpans) {
    spanElements.forEach((spanElement) => {
      spanElement.outerHTML = spanElement.innerHTML;
    });
  }

  // Step 3: Remove the text object from the codes array

  const found = deleteTextFromCodeById(codeId, textIndex, codes.value);

  if (!found) {
    console.warn('Code not found');
  } else {
    axios
      .delete(
        `/projects/${projectId}/sources/${props.source.id}/codes/${codeId}/selections/${item.id}`
      )
      .then((response) => {
        usePage().props.flash.message = response.data.message;
      })
      .catch((error) => {
        console.error('Error deleting code:', error);
      });
  }
};

/**
 * Scrolls to a specific text position within the editor.
 * @param {number} start - The start character number position.
 * @param {number} end - The end character number position.
 */
function scrollToTextPosition(start /*, end */) {
  const editor = document.getElementById('editor');
  if (!editor) return;

  const selection = window.getSelection();
  const range = document.createRange();
  selection.removeAllRanges();
  let charCount = 0;
  let node;

  const walker = document.createTreeWalker(
    editor,
    NodeFilter.SHOW_TEXT,
    null,
    false
  );
  while ((node = walker.nextNode())) {
    if (charCount + node.textContent.length >= start) {
      // Found the start position
      range.setStart(node, start - charCount);
      range.collapse(true); // Collapse the range to the start position

      // Create and insert the temporary marker
      const marker = document.createElement('span');
      marker.style.opacity = '0'; // Make the marker invisible
      range.insertNode(marker);

      marker.scrollIntoView({ behavior: 'smooth', block: 'center' });

      // Optionally, remove the marker after scrolling
      setTimeout(() => marker.remove(), 50); // Adjust timeout as needed

      break; // Exit the loop after scrolling
    }
    charCount += node.textContent.length;
  }
}

const codingStyles = [
  {
    name: 'Background',
    description: 'use background color to code this document',
  },
  {
    name: 'Underline',
    description: 'use underline color to code this document',
  },
];
let highlightMode = ref(codingStyles[0].name); // 'background' or 'underline'

/**
 * filter the codes array
 * @type {ComputedRef<unknown>}
 * see https://vuejs.org/api/reactivity-utilities.html#torefs
 */

const filteredCodes = computed(() => {
  // Ensure activeCodebook is an array, even if it's just one value
  const activeCodebookIds = Array.isArray(activeCodebook.value)
    ? activeCodebook.value
    : [activeCodebook.value];

  // Convert all values in the array to integers, in case they are strings
  const activeCodebookIdIntegers = activeCodebookIds.map((id) =>
    typeof id === 'string' ? parseInt(id, 10) : id
  );

  // Filter codes based on the active codebooks
  const codesForActiveCodebooks = codes.value.filter((code) =>
    activeCodebookIdIntegers.includes(code.codebook)
  );

  // If there's no search query, return the codes for the active codebooks
  if (!searchQuery.value) return codesForActiveCodebooks;

  // If there is a search query, filter the codes for the active codebooks based on the search query
  return codesForActiveCodebooks.filter((code) =>
    code.title.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const codebookDetails = ref(
  new Map(
    props.codebooks.map((cb) => [
      cb.id,
      {
        name: cb.name,
        description: cb.description ?? '',
        showDescription: false,
      },
    ])
  )
);

// Toggle focus mode
const toggleFocusMode = () => {
  focusMode.value = !focusMode.value;
  const elem = document.documentElement;

  if (isFullscreen.value) {
    closeFullscreen();
  } else {
    openFullscreen(elem);
  }
  isFullscreen.value = !isFullscreen.value;
  focusModeNavigation(focusMode.value);
};

const focusModeNavigation = (focus) => {
  const navigationElement = document.getElementById('navigation');
  if (navigationElement) {
    navigationElement.style.display = focus ? 'none' : 'block';
  }
};

const onFullScreenChange = () => {
  if (document.fullscreenElement === null) {
    isFullscreen.value = false;
    focusMode.value = false;
    focusModeNavigation(false);
  }
};

const lowerOpacityOfOthers = (codeId) => {
  if (!consultCodes.value) return;
  const spans = document.querySelectorAll('#editor span');
  spans.forEach((span) => {
    if (span.getAttribute('data-id') !== codeId) {
      const originalColor = window.getComputedStyle(span).backgroundColor;
      if (originalColor !== 'rgba(0, 0, 0, 0)') {
        // Skip transparent backgrounds
        span.style.backgroundColor = changeRGBOpacity(originalColor, 0.25);
      }
    }
  });
};

const resetOpacityOfOthers = (codeId) => {
  if (!consultCodes.value) return;
  const spans = document.querySelectorAll('#editor span');
  spans.forEach((span) => {
    if (span.getAttribute('data-id') !== codeId) {
      const originalColor = window.getComputedStyle(span).backgroundColor;
      if (originalColor !== 'rgba(0, 0, 0, 0)') {
        // Skip transparent backgrounds
        span.style.backgroundColor = changeRGBOpacity(
          originalColor,
          opacityForNewColors
        );
      }
    }
  });
};

const toggleOrResetOpacityForSingleCode = (codeId) => {
  // Query only the span elements that have the specified code ID
  const spans = document.querySelectorAll(`#editor span[data-id="${codeId}"]`);

  if (spans.length === 0) return; // Exit if no matching spans

  // Get the current opacity from the first span element
  const firstSpan = spans[0];
  const originalColor = window.getComputedStyle(firstSpan).backgroundColor;
  const colorArray = originalColor.split(',');
  let currentOpacity;

  if (colorArray.length === 4) {
    // rgba
    currentOpacity = parseFloat(colorArray[3].trim().slice(0, -1));
  } else {
    // rgb
    currentOpacity = 1;
  }

  // Determine the new opacity
  const newOpacity = currentOpacity === 1 ? 0.5 : 1;

  // Loop through each span to update its background color
  spans.forEach((span) => {
    const originalColor = window.getComputedStyle(span).backgroundColor;
    if (originalColor !== 'rgba(0, 0, 0, 0)') {
      // Skip transparent backgrounds
      span.style.backgroundColor = changeRGBOpacity(originalColor, newOpacity);
    }
  });

  // Update the color in the `codes.value` array for the specific code
  const code = codes.value.find((c) => c.id === codeId);
  if (code) {
    const originalColor = code.color;
    code.color = changeRGBOpacity(originalColor, newOpacity);
  }
};

const updateCodeColor = (code, newOpacity) => {
  const originalColor = code.color;
  code.color = changeRGBOpacity(originalColor, newOpacity);

  // Traverse the children
  if (Array.isArray(code.children) && code.children.length > 0) {
    code.children.forEach((childCode) => {
      updateCodeColor(childCode, newOpacity);
    });
  }
};

const globalBackgroundOpacity = ref(-1);
const toggleOrResetOpacity = (newOpacity) => {
  // Get all the span elements inside the editor that have a data-id attribute
  const spans = document.querySelectorAll('#editor span[data-id]');
  spans.forEach((span) => {
    let code = findCodeById(codes.value, span.dataset.id).code;
    span.style.backgroundColor = changeRGBOpacity(code.color, newOpacity);
  });

  codes.value.forEach((code) => {
    updateCodeColor(code, newOpacity);
  });
  globalBackgroundOpacity.value = newOpacity;
};

function codeThisFile(file) {
  if (file.id === props.source.id) return;
  const isConfirmed = window.confirm(
    'Are you sure you want to close this file? If you are connected to internet, everything is saved'
  );
  if (!isConfirmed) return;
  router.get(route('source.go-and-code', file.id));
}

function toggleFileSubMenu() {
  showFilesMenu.value = !showFilesMenu.value;
}

function changeRGBOpacity(rgba, opacity) {
  const rgbaValues = rgba.match(/[\d.]+/g);
  if (rgbaValues && rgbaValues.length >= 3) {
    return `rgba(${rgbaValues[0]}, ${rgbaValues[1]}, ${rgbaValues[2]}, ${opacity})`;
  }
  return rgba;
}

const hideAllCodes = () => {
  const editor = document.getElementById('editor');
  const spans = editor.querySelectorAll('span');

  spans.forEach((span) => {
    span.style.backgroundColor = 'rgba(0, 0, 0, 0)';
  });
};
const saveAllCodes = () => {
  if (
    codes.value.some((e) => {
      return e.editable === true;
    })
  ) {
    saveAllCodesRecursive(codes.value, codeItems.value);
  }
};
const saveAllCodesRecursive = (codesArray, codeItemsArray) => {
  codesArray.forEach((code, index) => {
    if (code.editable === true) {
      saveCodeTitle(code.id);
    }
    code.editable = false;
    let spanElement = codeItemsArray[index]?.querySelector('span');
    if (spanElement) {
      code.title = spanElement.textContent;
    }

    // If this code has children, recurse into them

    if (code.children && code.children.length > 0) {
      // Assuming you have a similar ref array for child items
      saveAllCodesRecursive(code.children, childCodeItems);
    }
  });
};

const handleCodeItemsClickOutside = (/* label */) => {
  if (codes.value.length === 0) return;
  saveAllCodes();
};

const handleDropdownClickOutsideRecursive = (codesArray) => {
  if (!codesArray) {
    return; // Base case: exit if the array is undefined or null
  }

  codesArray.forEach((code /*, index */) => {
    if (!code) {
      return; // Skip if the code is not defined
    }

    if (code.justOpened) {
      code.justOpened = false;
      return;
    }

    if (code.dropdownOpen) {
      code.dropdownOpen = false;
    }

    // If this item has children, recurse into them
    if (code.children && code.children.length > 0) {
      handleDropdownClickOutsideRecursive(code.children);
    }
  });
};

const handleCodeDescriptionClickOutsideRecursive = (codesArray) => {
  if (!codesArray) {
    return; // Base case: exit if the array is undefined or null
  }

  codesArray.forEach((code /*, index */) => {
    if (!code) {
      return; // Skip if the code is not defined
    }

    if (code.showEditDescription) {
      code.showEditDescription = false;
      return;
    }

    // If this item has children, recurse into them
    if (code.children && code.children.length > 0) {
      handleCodeDescriptionClickOutsideRecursive(code.children);
    }
  });
};

const handleCodeDescriptionClickOutside = () => {
  handleCodeDescriptionClickOutsideRecursive(codes.value);
};

const handleDropdownClickOutside = () => {
  handleDropdownClickOutsideRecursive(codes.value);
};

const handleContextMenuClickOutside = () => {
  const contextMenu = document.getElementById('contextMenu');
  contextMenu.classList.add('hidden');
};

const applyStyleToSpans = (code) => {
  const codeId = code.id;
  const spans = document.querySelectorAll(`span[data-id="${codeId}"]`);

  // Apply the same logic as in your toggleVisibilityInEditor function
  spans.forEach((span) => {
    if (span.style.backgroundColor === 'rgba(0, 0, 0, 0)') {
      span.style.backgroundColor = code.color;
    }
  });
};

const applyStylesRecursively = (codeArray) => {
  codeArray.forEach((code) => {
    // Apply styles to the current code
    applyStyleToSpans(code);

    // Recursively apply styles to children
    if (code.children && code.children.length > 0) {
      applyStylesRecursively(code.children);
    }
  });
};

const showAllCodes = () => {
  // Start the recursive function with the root codes array
  applyStylesRecursively(codes.value);
};

const toggleVisibilityInEditor = (codeId) => {
  const spans = document.querySelectorAll(`#editor span[data-id="${codeId}"]`);
  let code = findCodeById(codes.value, codeId).code;

  spans.forEach((span) => {
    if (span.style.backgroundColor === 'rgba(0, 0, 0, 0)') {
      span.style.backgroundColor = code.color;
    } else {
      span.style.backgroundColor = 'rgba(0, 0, 0, 0)';
    }
  });
};

const openModal = (index) => {
  selectedCode.value = codes.value[index];
  showModal.value = true;
};

const deleteCode = (codeId) => {
  // Find the code to delete using the provided codeId
  const codeToDelete = findCodeById(codes.value, codeId).code;
  const codeToSplice = findCodeById(codes.value, codeId);

  // Check if the code has children
  if (codeToDelete.children && codeToDelete.children.length > 0) {
    alert('This code has children and cannot be deleted.');
    return;
  }

  // Show a confirmation dialog
  const isConfirmed = window.confirm(
    'Are you sure you want to delete this code?'
  );

  // Only proceed if the user confirms
  if (isConfirmed) {
    // Make an Axios DELETE request
    axios
      .delete(
        `/projects/${projectId}/sources/${props.source.id}/codes/${codeId}`
      )
      .then((response) => {
        // Handle success

        // Find all spans with an id that matches the code id
        const spans = document.querySelectorAll(`span[data-id="${codeId}"]`);

        // Replace each span with its inner HTML
        spans.forEach((span) => {
          span.outerHTML = span.innerHTML;
        });

        // Remove the code from the codes array
        codeToSplice.parentArray.splice(codeToSplice.index, 1);

        usePage().props.flash.message = response.data.message;
      })
      .catch((error) => {
        // Handle error
        console.error('Error deleting code:', error);
      });
  }
};

function generateUUID() {
  // https://developer.mozilla.org/en-US/docs/Web/API/Crypto/randomUUID
  return crypto.randomUUID();

  // return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(
  //     /[xy]/g,
  //     function (c) {
  //         var r = (Math.random() * 16) | 0,
  //             v = c == "x" ? r : (r & 0x3) | 0x8;
  //         return v.toString(16);
  //     }
  // );
}

// This function generates random light colors for highlighting
function getRandomColor() {
  const red = Math.floor(Math.random() * 128 + 128);
  const green = Math.floor(Math.random() * 128 + 128);
  const blue = Math.floor(Math.random() * 128 + 128);
  return `rgba(${red}, ${green}, ${blue}, ${opacityForNewColors})`;
}

// Drag and drop related functions
const setupDragAndDrop = () => {
  // Make the #editor draggable
  const editor = document.getElementById('editor');

  // Save the selected text when the user starts dragging
  editor.addEventListener('dragstart', handleDragStart);

  const dropzone = document.getElementById('dropzone');

  // Allow the dropzone to accept drops
  ['dragenter', 'dragover'].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
    });
  });

  dropzone.addEventListener('drop', handleDrop);
};

const handleDragCodeStart = (event, index, parentId = null, childId = null) => {
  event.dataTransfer.setData(
    'text/plain',
    JSON.stringify({
      index: index,
      parentId: parentId,
      childId: childId,
    })
  );
};

function getSelectionCharacterOffsetWithin(range, element) {
  let start = 0;
  let end = 0;
  const doc = element.ownerDocument || element.document;
  const win = doc.defaultView || doc.parentWindow;
  let sel;

  function getTextNodesIn(node) {
    let textNodes = [];
    if (node.nodeType == 3) {
      textNodes.push(node);
    } else {
      const children = node.childNodes;
      for (let i = 0; i < children.length; i++) {
        textNodes.push.apply(textNodes, getTextNodesIn(children[i]));
      }
    }
    return textNodes;
  }

  if (typeof win.getSelection != 'undefined') {
    sel = win.getSelection();
    if (sel.rangeCount > 0) {
      let preCaretRange = range.cloneRange();
      let textNodes = getTextNodesIn(element);
      if (textNodes.length > 0) {
        preCaretRange.setStart(textNodes[0], 0);
        preCaretRange.setEnd(range.startContainer, range.startOffset);
        start = preCaretRange.toString().length;
        preCaretRange.setEnd(range.endContainer, range.endOffset);
        end = preCaretRange.toString().length;
      }
    }
  } else if ((sel = doc.selection) && sel.type !== 'Control') {
    const textRange = sel.createRange();
    const preCaretTextRange = doc.body.createTextRange();
    preCaretTextRange.moveToElementText(element);
    preCaretTextRange.setEndPoint('EndToStart', textRange);
    start = preCaretTextRange.text.length;
    preCaretTextRange.setEndPoint('EndToEnd', textRange);
    end = preCaretTextRange.text.length;
  }
  return { start: start, end: end };
}

let storedSelectionRange;

/**
 * function to handle text highlighting and node creation
 * this is always called from the document and never from DB population
 * @param range
 * @param targetCode
 * @param text
 */
function handleTextHighlighting(range, targetCode, text) {
  // safari behavior is different, so we store the range and use it if it matches the text
  if (
    isSafari &&
    storedSelectionRange &&
    storedSelectionRange.toString() === text
  ) {
    range = storedSelectionRange; // Use the stored range if it matches the text
  }

  const offsets = getSelectionCharacterOffsetWithin(
    range,
    document.getElementById('editor')
  );
  let textId = generateUUID();
  highlightRange(range, targetCode.color, targetCode.id, textId);

  const payload = {
    textId: textId,
    text: text,
    start_position: offsets.start,
    end_position: offsets.end,
  };

  axios
    .post(
      `/projects/${projectId}/sources/${props.source.id}/codes/${targetCode.id}`,
      payload
    )
    .then((/* response */) => {})
    .catch((error) => {
      console.error('There was an error saving the selection:', error);
    });

  // Add the selected text to the clicked code's highlightedSpans
  // As there's no new node created, you might just want to store the range, text, and offsets.
  targetCode.text.push({
    id: textId,
    text: text,
    start: offsets.start,
    end: offsets.end,
  });
}

function nextNode(node) {
  if (node.hasChildNodes()) {
    return node.firstChild;
  } else {
    while (node && !node.nextSibling) {
      node = node.parentNode;
    }
    if (!node) {
      return null;
    }
    return node.nextSibling;
  }
}

function getNodesInRange(range) {
  const startNode = range.startContainer;
  const endNode = range.endContainer;
  let pastStartNode = false;
  const nodes = [];
  let node = startNode;
  while (node && node !== endNode) {
    pastStartNode = pastStartNode || node === startNode;
    if (node.nodeType === 3 && (pastStartNode || node.nodeValue.length > 0)) {
      nodes.push(node);
    } else if (pastStartNode && !node.hasChildNodes()) {
      nodes.push(node);
    }
    node = nextNode(node);
  }
  nodes.push(endNode);
  return nodes;
}

// Call the function like this:
// markTextNodesInRange(yourRange, 'red', 'uniqueTextId');

function findNestedCode(codes, targetId) {
  for (let code of codes) {
    if (code.id === targetId) {
      return code;
    }

    if (code.children.length > 0) {
      const found = findNestedCode(code.children, targetId);
      if (found) {
        return found;
      }
    }
  }
  return null;
}

/**
 * flatten the code items children
 * @param codeItemComponents
 * @returns {*[]}
 */
const flattenCodeItems = (codeItemComponents) => {
  let flattened = [];

  codeItemComponents.forEach((compRef) => {
    if (compRef.childCodeItems) {
      flattened.push(compRef.childCodeItems);
    }

    if (compRef.codeItemComponents) {
      flattened.push(...compRef.codeItemComponents);
    }
  });

  return flattened;
};

/**
 * Handle the drop event from dragging the text to the codes list
 * todo CURRENTLY BUGGED
 * @param event
 */
const handleDrop = (event) => {
  event.preventDefault();

  const droppedText = event.dataTransfer.getData('text/plain');
  if (droppedText !== selectedText) return;

  // Combine both arrays
  const allCodeItems = [
    ...codeItems.value,
    ...flattenCodeItems(codeItemComponents.value),
  ];
  console.log(allCodeItems);
  console.log(codeItems.value);

  const dropTargetElement = allCodeItems.find((item) => {
    return item === event.target || item.contains(event.target);
  });

  if (dropTargetElement) {
    const dropTargetId = dropTargetElement.dataset.id;

    const targetCode = findNestedCode(codes.value, dropTargetId);
    if (targetCode) {
      handleTextHighlighting(
        window.getSelection().getRangeAt(0),
        targetCode,
        droppedText
      );
    } else {
      console.warn('Target code not found');
    }
  } else {
    console.warn('Drop target element not found');
  }
};

const findCodeById = (codesArray, id, parentId = null) => {
  for (let i = 0; i < codesArray.length; i++) {
    const code = codesArray[i];
    if (code.id === id) {
      return {
        code: code,
        index: i,
        parentArray: codesArray,
        parentId: parentId,
      };
    }

    if (code.children && code.children.length > 0) {
      parentId = code.id;
      const foundCode = findCodeById(code.children, id, parentId);
      if (foundCode) {
        return foundCode;
      }
    }
  }

  return null;
};

const handleDropFromCodesList = (event) => {
  event.preventDefault();

  const droppedData = JSON.parse(event.dataTransfer.getData('text/plain'));

  const droppedCodeIndex = droppedData.index;

  const childId = droppedData.childId;

  let droppedCode;

  if (childId) {
    const found = findCodeById(codes.value, childId);

    droppedCode = found ? found.code : null;
  } else {
    droppedCode = codes.value[droppedCodeIndex];
  }

  const selectedText = window.getSelection().toString();
  if (!selectedText) return;

  handleTextHighlighting(
    window.getSelection().getRangeAt(0),
    droppedCode,
    selectedText
  );
};

const highlightAndAddTextToCode = async (index, currentId, parentId = null) => {
  let targetCode;
  let isReassigning = false;
  let textObject;

  if (isSafari) {
    selectedRange = storedSelectionRange; // Use the stored range if it matches the text
    if (selectedRange.startOffset === selectedRange.endOffset) {
      usePage().props.flash.message =
        'There was an error, try another way to code text';
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

    try {
      const response = await axios.post(
        `/projects/${projectId}/sources/${props.source.id}/codes/${targetCode.id}/selections/${textObject.id}/change-code`,
        {
          oldCodeId: rightClickedText.value.getAttribute('data-id'),
          newCodeId: targetCode.id,
        }
      );

      // Handle the response from the server
      if (!response.data.success) {
        console.error('Failed to reassign text on the server.');
      }
    } catch (error) {
      console.error('Error reassigning text:', error);
    }
  } else {
    // Handle text highlighting for new text
    handleTextHighlighting(selectedRange, targetCode, selectedText);
  }
  handleContextMenuClickOutside();
};

const handleDragStart = (event) => {
  selectedText = window.getSelection().toString();
  event.dataTransfer.setData('text/plain', selectedText);
};

const addCodeToList = async () => {
  let idLocal = generateUUID();
  let color = getRandomColor();

  const adjectives = [
    'Amazing',
    'Creative',
    'Brilliant',
    'Efficient',
    'Majestic',
    'Interesting',
    'Meaningful',
    'Networked',
  ];
  const nouns = [
    'Code',
    'Function',
    'Method',
    'Block',
    'Snippet',
    'Conversation',
  ];

  const randomAdjective =
    adjectives[Math.floor(Math.random() * adjectives.length)];
  const randomNoun = nouns[Math.floor(Math.random() * nouns.length)];

  const randomName = `${randomAdjective} ${randomNoun}`;

  // Construct code object
  const codeObject = {
    id: idLocal,
    text: [],
    color: color,
    editable: true, // edit the code title right away
    codebook: parseInt(activeCodebook.value, 10), //somehow int works and not string
    title: randomName,
    order: codes.value.length,
    showText: false,
    children: [],
    visibleInEditor: true,
    dropdownOpen: false,
    justOpened: false,
    description: '',
    showEditDescription: false,
    showDescription: false,
  };

  // Add to local state or whatever you're using
  codes.value.push(codeObject);

  try {
    const response = await axios.post(
      `/projects/${projectId}/codes`,
      codeObject
    );
    // Update the local ID with the received ID, assuming the received ID is at response.data.id
    const localIndex = codes.value.findIndex((code) => code.id === idLocal);

    if (localIndex !== -1) {
      codes.value[localIndex].id = response.data.id; // Replace this with the actual path to the id in the response
    }
    usePage().props.flash.message = response.data.message;
  } catch (error) {
    usePage().props.flash.message = 'error while saving code';
    // Handle error
    console.error(error);
  }
};

/**
 *
 * @param codeId
 * @param codesArray
 * @returns {Promise<boolean>}
 */
const addCodeToChildrenRecursive = async (codeId, codesArray = codes.value) => {
  if (!codesArray) {
    return false;
  }

  for (let i = 0; i < codesArray.length; i++) {
    const currentCode = codesArray[i];

    if (!currentCode) continue; // Skip undefined or null items

    if (currentCode.id === codeId) {
      let title;
      if (currentCode.title.includes('subcode')) {
        // It's a grandchild
        title = `${currentCode.title.split(' subcode')[0]} sub-subcode ${
          currentCode.children.length
        }`;
      } else {
        // It's a child
        title = `${currentCode.title} subcode ${currentCode.children.length}`;
      }
      let localId = `${codeId}children${currentCode.children.length}`;
      const newChildCode = {
        id: localId,
        title: title,
        color: currentCode.color,
        codebook: currentCode.codebook,
        children: [],
        editable: true, // make the title of the code editable already
        showText: false,
        dropdownOpen: false,
        justOpened: false,
        text: [],
        parent_id: currentCode.id,
        description: '',
        showEditDescription: false,
        showDescription: false,
      };

      // if somehow the currentcode has no children, create the children array to avoid errors
      if (!currentCode.children) currentCode.children = [];
      currentCode.children.push(newChildCode);

      try {
        const response = await axios.post(
          `/projects/${projectId}/codes`,
          newChildCode
        );
        // Update the local ID with the received ID, assuming the received ID is at response.data.id
        let localCode = findCodeById(codes.value, localId);

        // assign the id generated in the backend.
        if (localCode !== -1) {
          localCode.parentArray[localCode.index].id = response.data.id;
        }
        // close any open dropdowns
        currentCode.dropdownOpen = false;
        usePage().props.flash.message = response.data.message;
      } catch (error) {
        usePage().props.flash.message = 'error while saving code';
        // Handle error
        console.error(error);
      }
      return true;
    } else if (currentCode.children && currentCode.children.length > 0) {
      const added = await addCodeToChildrenRecursive(
        codeId,
        currentCode.children
      );
      if (added) return true;
    }
  }

  return false;
};

const moveCodeUp = (codeId) => {
  const found = findCodeById(codes.value, codeId);
  if (found && found.index > 0) {
    const temp = found.parentArray[found.index];
    found.parentArray[found.index] = found.parentArray[found.index - 1];
    found.parentArray[found.index - 1] = temp;
  }
};

const moveCodeDown = (codeId) => {
  const found = findCodeById(codes.value, codeId);
  if (found && found.index < found.parentArray.length - 1) {
    const temp = found.parentArray[found.index];
    found.parentArray[found.index] = found.parentArray[found.index + 1];
    found.parentArray[found.index + 1] = temp;
  }
};

const deleteTextFromCodeFromContextMenu = (event) => {
  event.preventDefault();

  let noSpans = false;
  if (!rightClickedText.value) return;

  // Step 1: Get the clicked span element
  const clickedElement = rightClickedText; // you might have to store this during the `showContextMenu` event
  const codeId = clickedElement.value.getAttribute('data-id');
  const textId = clickedElement.value.getAttribute('data-text-id');

  const isConfirmed = window.confirm(
    'Are you sure you want to delete this text from the code?'
  );
  if (!isConfirmed) return;

  // Step 2: Query all span elements by their data-text-id attribute
  const spanElements = document.querySelectorAll(`[data-text-id="${textId}"]`);

  if (spanElements.length === 0) {
    console.warn('No spans found');
    noSpans = true;
  }

  // Step 3: Loop through all span elements and replace their outerHTML with their innerHTML
  // Keep formatting here when deleting <br> problem?
  if (!noSpans) {
    spanElements.forEach((spanElement) => {
      spanElement.outerHTML = spanElement.innerHTML;
    });
  }

  // Step 4: Locate the code and text object in your data model by codeId and textId
  const found = deleteTextFromCodeById(codeId, textId, codes.value);

  if (!found) {
    console.warn('Code or Text not found');
    return;
  } else {
    axios
      .delete(
        `/projects/${projectId}/sources/${props.source.id}/codes/${codeId}/selections/${textId}`
      )
      .then((response) => {
        usePage().props.flash.message = response.data.message;
      })
      .catch((error) => {
        console.error('Error deleting code:', error);
      });
  }

  // Reset rightClickedText
  rightClickedText.value = null;

  // If you're using Vue refs, don't forget to trigger the reactivity
  codes.value = [...codes.value];
  handleContextMenuClickOutside();
};

const showContextMenu = (event) => {
  if (event.ctrlKey || event.metaKey) {
    // Allow the browser's context menu to appear
    return;
  }
  event.preventDefault();

  rightClickedText.value = null;
  const clickedElement = event.target;
  let isCode = false;

  if (isSafari) window.getSelection().removeAllRanges();
  selectedText =
    isSafari && storedSelectionRange
      ? storedSelectionRange.toString()
      : window.getSelection().toString();
  if (
    clickedElement.tagName === 'SPAN' &&
    selectedText === '' &&
    clickedElement.hasAttribute('data-id') &&
    clickedElement.hasAttribute('data-text-id')
  ) {
    isCode = true;
  }

  const contextMenu = document.getElementById('contextMenu');
  const deleteCodeBtn = document.getElementById('deleteCodeBtn');
  const windowHeight = window.innerHeight;

  if (isCode) {
    deleteCodeBtn.classList.remove('hidden');
    rightClickedText.value = event.target;
  } else {
    deleteCodeBtn.classList.add('hidden');

    if (selectedText === '') return;
    selectedRange =
      isSafari && storedSelectionRange
        ? storedSelectionRange
        : window.getSelection().getRangeAt(0);
  }

  contextMenu.style.left = `${event.clientX}px`;
  contextMenu.classList.remove('hidden');

  // Force a slight layout update so we can measure the contextMenu's dimensions
  contextMenu.offsetHeight;

  if (event.clientY + contextMenu.offsetHeight > windowHeight) {
    // If the context menu would go out of bounds, adjust its top position
    contextMenu.style.top = `${windowHeight - contextMenu.offsetHeight}px`;
  } else {
    contextMenu.style.top = `${event.clientY}px`;
  }
};

const saveCodeTitle = async (codeId, options = {}) => {
  let codeObject = findCodeById(codes.value, codeId);
  codeObject.code.editable = false;

  if (options.current !== options.prev && options.current === '') {
    codeObject.code = { title: codeObject.code.title };
    codeObject.parentArray[codeObject.index].title = options.prev;
    return false;
  }

  codeObject.code.title = options.current ?? options.prev;

  // Axios call to update the title in the DB
  try {
    await axios.post(
      `/projects/${projectId}/codes/${codeObject.code.id}/update-title`,
      {
        title: codeObject.code.title,
      }
    );
  } catch (error) {
    console.error('Error updating title in the database', error);
  }
};

const hexToRgb = (hex) => {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result
    ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
      }
    : null;
};

const rgbToHex = (r, g, b) => {
  if (
    typeof r === 'undefined' ||
    typeof g === 'undefined' ||
    typeof b === 'undefined'
  ) {
    console.error('One of the RGB components is undefined.');
    return null;
  }

  const componentToHex = (c) => {
    const hex = c.toString(16);
    return hex.length === 1 ? '0' + hex : hex;
  };

  return '#' + componentToHex(r) + componentToHex(g) + componentToHex(b);
};

const extractRGB = (colorString) => {
  let matches = colorString.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
  if (!matches) {
    matches = colorString.match(/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/);
  }
  if (matches) {
    return {
      r: parseInt(matches[1], 10),
      g: parseInt(matches[2], 10),
      b: parseInt(matches[3], 10),
      a: matches[4] ? parseFloat(matches[4]) : 1,
    };
  }

  // Check for hex color format
  matches = colorString.match(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/);
  if (matches) {
    let hex = matches[1];
    if (hex.length === 3) {
      hex = hex
        .split('')
        .map((char) => char + char)
        .join(''); // convert #abc to #aabbcc
    }
    return {
      r: parseInt(hex.substring(0, 2), 16),
      g: parseInt(hex.substring(2, 4), 16),
      b: parseInt(hex.substring(4, 6), 16),
      a: 1,
    };
  }

  return null;
};

// Function to update the color of a specific code using its ID
const updateCodeColorFromId = async (codeId, newColor) => {
  // Find the specific code by its ID using a recursive function
  const findCodeById = (codes, id) => {
    for (let code of codes) {
      if (code.id === id) return code;
      if (code.children) {
        const childCode = findCodeById(code.children, id);
        if (childCode) return childCode;
      }
    }
    return null;
  };

  const code = findCodeById(codes.value, codeId);
  if (!code) return; // If the code with the given ID is not found, exit

  // Update the code's color
  code.color = newColor;

  // Update the color of all highlighted text elements with the same id as the code
  const spans = document.querySelectorAll(`span[data-id="${codeId}"]`);
  spans.forEach((span) => {
    if (span) {
      if (highlightMode.value === 'Background') {
        span.style.backgroundColor = newColor;
      } else {
        span.style.borderColor = newColor;
      }
    }
  });

  // Axios call to update the color in the DB
  try {
    await axios.post(`/projects/${projectId}/codes/${codeId}/update-color`, {
      color: newColor,
    });
    // Handle the response as needed. For example, show a success message or update the UI.
  } catch (error) {
    console.error('Error updating color in the database', error);
    // Handle the error, for example, show an error message to the user.
  }
};

const updateColor = async (event, index, codeId) => {
  const hexColor = event.target.value;

  // Convert hex to RGB
  const rgbColor = hexToRgb(hexColor); // Assuming you have a hexToRgb function

  const newColor = `rgba(${rgbColor.r}, ${rgbColor.g}, ${rgbColor.b}, ${opacityForNewColors})`;

  // Update the color of the selected code using its ID
  await updateCodeColorFromId(codeId, newColor);
};

const toggleCodeById = (codeId, codesArray) => {
  for (let i = 0; i < codesArray.length; i++) {
    const currentCode = codesArray[i];

    if (!currentCode) continue; // Skip undefined or null items

    // Found the code with the matching id
    if (currentCode.id === codeId) {
      currentCode.showText = !currentCode.showText;
      return true;
    }

    // If the code has children, search among them
    if (
      Array.isArray(currentCode.children) &&
      currentCode.children.length > 0
    ) {
      const found = toggleCodeById(codeId, currentCode.children);
      if (found) return true;
    }
  }

  return false;
};

const toggleCodeText = (codeId) => {
  const found = toggleCodeById(codeId, codes.value);

  if (!found) {
    console.warn(`Code with id ${codeId} not found.`);
  }
};

// Helper function to toggle showText recursively
const toggleShowTextRecursively = (codesArray, newState) => {
  for (let code of codesArray) {
    code.showText = newState;
    if (code.children.length > 0) {
      toggleShowTextRecursively(code.children, newState);
    }
  }
};

const toggleAllCodesText = () => {
  // Check if at least one 'showText' is false
  const atLeastOneFalse = codes.value.some((code) => !code.showText);

  // If at least one 'showText' is false, set all to true
  if (atLeastOneFalse) {
    toggleShowTextRecursively(codes.value, true);
  } else {
    // Otherwise, set all to false
    toggleShowTextRecursively(codes.value, false);
  }
};

// Create an effect for side-effects whenever codes value change
watch(
  codes,
  (newCodes /*, oldCodes */) => {
    newCodes.forEach((code) => {
      let codeElement = document.querySelector(`[data-id="${code.id}"]`);
      codeIdToElementMap.set(code.id, codeElement);
    });
  },
  { immediate: true }
);

function highlightStoredTextsFromDB(editorElement) {
  clearHighlights(editorElement);

  const highlightTexts = (codes) => {
    codes.forEach((code) => {
      code.text.forEach((text) => {
        const range = recreateRangeFromPositions(
          text.start,
          text.end,
          editorElement
        );
        highlightRange(range, code.color, code.id, text.id);
      });

      // Check for children and recurse if they exist
      if (code.children && code.children.length > 0) {
        highlightTexts(code.children);
      }
    });
  };

  // Use filteredCodes here to ensure we are only highlighting the codes from the active codebook
  highlightTexts(filteredCodes.value);
}

/**
 *
 * @param range
 * @param color
 * @param id
 * @param textId
 *
 */
function highlightRange(range, color, id, textId) {
  if (range.collapsed) {
    return;
  }

  // If start or end of range is in the middle of a text node, split it
  if (
    range.startContainer.nodeType === 3 &&
    range.startOffset !== range.startContainer.length
  ) {
    range.setStart(range.startContainer.splitText(range.startOffset), 0);
  }
  if (range.endContainer.nodeType === 3 && range.endOffset !== 0) {
    range.setEnd(range.endContainer.splitText(range.endOffset), 0);
  }

  // Adjust end boundary if it's at the start of a text node
  if (
    range.endContainer.nodeType === 3 &&
    range.endOffset === 0 &&
    range.endContainer.previousSibling &&
    range.endContainer.previousSibling.nodeType === 3
  ) {
    range.setEnd(
      range.endContainer.previousSibling,
      range.endContainer.previousSibling.length
    );
  }

  // If the range ends just before a non-text node, adjust it to include that node.
  if (range.endOffset === range.endContainer.childNodes.length) {
    const nextNode = range.endContainer.childNodes[range.endOffset];
    if (nextNode && nextNode.nodeType !== 3) {
      range.setEndAfter(nextNode);
    }
  }

  // If the range starts just after a non-text node, adjust it to start before that node.
  if (range.startOffset === 0) {
    const prevNode = range.startContainer.childNodes[range.startOffset - 1];
    if (prevNode && prevNode.nodeType !== 3) {
      range.setStartBefore(prevNode);
    }
  }

  const nodes = getNodesInRange(range);
  nodes.forEach((node) => {
    wrapNodeInSpan(node, color, id, textId);
  });
}

/**
 * Helper function to wrap a range's contents in a span
 * @param range
 * @param color
 * @param id
 * @param textId
 * @returns {HTMLSpanElement}
 */
function wrapRangeInSpan(range, color, id, textId) {
  // Create a new span element and style it
  const span = document.createElement('span');
  span.style.backgroundColor = color;
  span.dataset.id = id;
  span.dataset.textId = textId;
  // Use the range to surround the selected content with the new span
  range.surroundContents(span);

  return span;
}

/**
 *  function to wrap nodes within a range in a span
 *  todo: exclude further tags from formatting
 * @param node
 * @param color
 * @param id
 * @param textId
 * @returns {HTMLSpanElement|*}
 */
function wrapNodeInSpan(node, color, id, textId) {
  if (node.nodeType === 3) {
    // Check if the node is a text node
    // Create a range to select the text content of the node
    const range = document.createRange();
    range.selectNodeContents(node);
    // Wrap the text node within a span using the above helper function
    return wrapRangeInSpan(range, color, id, textId);
  } else if (node.nodeName === 'BR') {
    // If the node is a <br> tag, skip wrapping it
    // this (might) avoid the bug that sometimes happens that an empty line is deleted from the text (visually)
    return; // Simply return without wrapping the <br> tag
  } else if (node.hasChildNodes()) {
    // If it's an element with children, recursively wrap its child nodes
    Array.from(node.childNodes).forEach((child) => {
      wrapNodeInSpan(child, color, id, textId);
    });
  } else {
    // For any other non-text element without children, apply background color and dataset properties
    node.style.backgroundColor = color;
    node.dataset.id = id;
    node.dataset.textId = textId;
    return node; // Return the element for any further use
  }
}

function clearHighlights(editorElement) {
  const spans = editorElement.querySelectorAll('span[data-text-id]');
  spans.forEach((span) => {
    // Move all children out of the span
    while (span.firstChild) {
      span.parentNode.insertBefore(span.firstChild, span);
    }
    // Remove the empty span
    span.parentNode.removeChild(span);
  });
}

function recreateRangeFromPositions(start, end, editor) {
  let range = new Range();

  let findBoundary = (boundary) => {
    let node;
    const walker = document.createTreeWalker(editor, NodeFilter.SHOW_TEXT);
    let charCount = 0;

    while ((node = walker.nextNode())) {
      if (charCount + node.length >= boundary) {
        return { node: node, offset: boundary - charCount };
      }
      charCount += node.length;
    }
    return null;
  };

  const startBoundary = findBoundary(start);
  const endBoundary = findBoundary(end);

  if (startBoundary && endBoundary) {
    range.setStart(startBoundary.node, startBoundary.offset);
    range.setEnd(endBoundary.node, endBoundary.offset);
  }

  return range;
}

function mapCodeProperties(code) {
  return {
    id: code.id,
    title: code.name,
    color: code.color,
    text: code.text ? code.text : [],
    editable: false,
    codebook: code.codebook,
    dropdownOpen: false,
    justOpened: false,
    showText: false,
    description: code.description ? code.description : '',
    showEditDescription: false,
    showDescription: false,
    children: reactive((code.children || []).map(mapCodeProperties)), // Recursively map children
  };
}

let isInitialLoad = ref(true);

const onScroll = () => {
  hasScrolled.value = window.scrollY > 100;
};

onMounted(async () => {
  document.addEventListener('fullscreenchange', onFullScreenChange);
  window.addEventListener('scroll', onScroll);
  window.addEventListener('resize', adjustSelectionInfoPosition);

  // Check if localStorage has sourceId and it matches the current source
  const storedSourceId = localStorage.getItem('sourceId');
  const storedCodebookString = localStorage.getItem('codebook');

  // If the stored sourceId matches the current source, use the stored codebook if available
  if (storedSourceId && storedSourceId == props.source.id) {
    // Check if storedCodebookString is not null and is a stringified array
    if (storedCodebookString && storedCodebookString.startsWith('[')) {
      const storedCodebook = JSON.parse(storedCodebookString);
      // Now that we have an actual array, we can map over it to parse integers
      activeCodebook.value = storedCodebook.map((cb) => parseInt(cb, 10));
    } else if (storedCodebookString) {
      // Convert single value to an array and then to integer
      activeCodebook.value = [parseInt(storedCodebookString, 10)];
    }
  } else {
    // The document is different or no sourceId in localStorage, use default behavior
    if (props.allCodes && props.allCodes.length > 0) {
      // Convert codebook ID to integer
      activeCodebook.value = [parseInt(props.allCodes[0].codebook, 10)];
    } else {
      activeCodebook.value = [];
    }
    localStorage.setItem('sourceId', props.source.id);
    localStorage.setItem('codebook', JSON.stringify(activeCodebook.value)); // Store as a stringified array
  }

  codes.value = props.allCodes.map(mapCodeProperties);

  document.addEventListener('selectionchange', () => {
    const selection = window.getSelection();
    /**
     * The Selection.isCollapsed read-only property returns a boolean value which indicates whether there is currently any text selected or not.
     * No text is selected when the selection's start and end points are at the same position in the content.
     * We need this case because, somehow, Safari selection collapses when you right-click
     */
    if (!selection.isCollapsed) {
      // We store the window selection range because Safari misbehaves when right-clicking
      storedSelectionRange = selection.getRangeAt(0).cloneRange();
    }

    const editor = document.getElementById('editor');
    if (!selection.isCollapsed && editor.contains(selection.anchorNode)) {
      const { start, end } = calculateSelectionCharPositions(editor);
      updateSelectionInfo(start, end);
    } else {
      updateSelectionInfo(0, 0);
    }
  });

  setupDragAndDrop();
  adjustSelectionInfoPosition();

  // Once the component is mounted, collect all children
  codeItemComponents.value.forEach((childComponent) => {
    if (typeof childComponent.collectChildren === 'function') {
      childComponent.collectChildren();
    }
  });

  codes.value.forEach((code) => {
    code.showText = false;
  });
  editor.innerHTML = props.source.content;
  const savedFontSize = localStorage.getItem('editorFontSize');
  if (savedFontSize) {
    fontSize.value = parseInt(savedFontSize, 10);
  }
  const savedLineHeight = localStorage.getItem('editorLineHeight');
  if (savedLineHeight) {
    lineHeight.value = parseFloat(savedLineHeight);
  }

  highlightStoredTextsFromDB(editor);

  isInitialLoad.value = false;
});

onBeforeUnmount(() => {
  document.removeEventListener('selectionchange', () => {
    const selection = window.getSelection();
    /**
     * The Selection.isCollapsed read-only property returns a boolean value which indicates whether there is currently any text selected or not.
     * No text is selected when the selection's start and end points are at the same position in the content.
     * We need this case because, somehow, Safari selection collapses when you right-click
     */
    if (!selection.isCollapsed) {
      // We store the window selection range because Safari misbehaves when right-clicking
      storedSelectionRange = selection.getRangeAt(0).cloneRange();
    }

    const editor = document.getElementById('editor');
    if (!selection.isCollapsed && editor.contains(selection.anchorNode)) {
      const { start, end } = calculateSelectionCharPositions(editor);
      updateSelectionInfo(start, end);
    }
  });
  window.removeEventListener('scroll', onScroll);
  document.removeEventListener('fullscreenchange', onFullScreenChange);
  //window.removeEventListener("resize", onResize);
});

watchEffect(() => {
  // Assume someData is the reactive variable that holds data affecting the child components
  // Whenever someData changes, this block of code will run
  if (codeItemComponents.value.length > 0) {
    codeItemComponents.value.forEach((childComponent) => {
      if (typeof childComponent.collectChildren === 'function') {
        childComponent.collectChildren();
      }
    });
  }
});

// Function to save description
const saveDescription = async (code) => {
  let description = code.description;
  await axios.post(
    `/projects/${projectId}/sources/${props.source.id}/codes/${code.id}/description`,
    {
      description,
    }
  );
};

const upgradeToParent = async (codeId) => {
  const found = findCodeById(codes.value, codeId);
  found.code.parent_id = null;

  // remove the child from the found.parent
  found.parentArray.splice(found.index, 1);

  // add the child to the main codes array
  codes.value.push(found.code);

  // make the call to remove the parent_id from the code in the backend
  try {
    const response = await axios.post(
      `/projects/${projectId}/sources/${props.source.id}/codes/${found.code.id}/remove-parent`
    );
    usePage().props.flash.message = response.data.message;
  } catch (error) {
    usePage().props.flash.message = 'error while updating code';
    // Handle error
    console.error(error);
  }
};

const upHierarchy = async (codeId) => {
  const found = findCodeById(codes.value, codeId);

  // find the parent of the parent
  const parentOfParent = findCodeById(codes.value, found.parentId);

  // remove the child from the found.parent
  found.parentArray.splice(found.index, 1);

  // add the child to the main codes array
  parentOfParent.parentArray.push(found.code);

  // make the call to remove the parent_id from the code in the backend
  try {
    const response = await axios.post(
      `/projects/${projectId}/sources/${props.source.id}/codes/${found.code.id}/up-hierarchy`
    );
    usePage().props.flash.message = response.data.message;
  } catch (error) {
    usePage().props.flash.message = 'error while updating code';
    // Handle error
    console.error(error);
  }
};

/** give children of this vue component the following methods (mainly the codeitem) **/
provide('handleDrag', handleDragCodeStart);
provide('codes', codes);
provide('childCodeItems', childCodeItems);
provide('moveCodeUp', moveCodeUp);
provide('moveCodeDown', moveCodeDown);
provide('toggleCodeText', toggleCodeText);
provide('toggleOrResetOpacityForSingleCode', toggleOrResetOpacityForSingleCode);
provide('toggleVisibilityInEditor', toggleVisibilityInEditor);
provide('deleteCode', deleteCode);
provide('openModal', openModal);
provide('addCodeToChildrenRecursive', addCodeToChildrenRecursive);
provide('updateColor', updateColor);
provide('rgbToHex', rgbToHex);
provide('extractRGB', extractRGB);
provide('handleDropdownClickOutside', handleDropdownClickOutside);
provide('handleDragCodeStart', handleDragCodeStart);
provide('handleCodeItemsClickOutside', handleCodeItemsClickOutside);
provide('deleteTextFromCode', deleteTextFromCode);
provide('lowerOpacityOfOthers', lowerOpacityOfOthers);
provide('resetOpacityOfOthers', resetOpacityOfOthers);
provide('saveCodeTitle', saveCodeTitle);
provide('saveDescription', saveDescription);
provide('handleCodeDescriptionClickOutside', handleCodeDescriptionClickOutside);
provide('openDescription', openCodeDescription);
provide('upgradeToParent', upgradeToParent);
provide('upHierarchy', upHierarchy);
provide('scrollToTextPosition', scrollToTextPosition);

const onCodebookCreated = (newCodebook) => {
  // Add the new codebook to the project's codebooks array
  activeCodebook.value.push(newCodebook);
};
</script>

<style scoped>
.contextMenuOption:hover {
  opacity: 0.7;
}

.editor-container {
  display: flex;
  flex-direction: row;
}

/* Dropdown menu item styles */
[role='menuitem'] {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: opacity 0.2s ease-in-out;
}

[role='menuitem']:hover {
  opacity: 0.7;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

#dropzone {
  transform: translateY(0);
  transition: top 1.5s ease-in-out; /* Adjust timings if desired */
}

/** quill styles **/
/* Alignment Classes */

:deep(.ql-align-center) {
  text-align: center;
}

:deep(.ql-align-justify) {
  text-align: justify;
}

:deep(.ql-align-right) {
  text-align: right;
}

/* Formatting Classes */
:deep(.ql-bold) {
  font-weight: bold;
}

:deep(.ql-italic) {
  font-style: italic;
}

:deep(.ql-underline) {
  text-decoration: underline;
}

:deep(.ql-strike) {
  text-decoration: line-through;
}

:deep(.ql-code-block) {
  /* Your code block styles here */
}

:deep(.ql-blockquote) {
  /* Your blockquote styles here */
}

:deep(.ql-link) {
  /* Your link styles here */
}

/* List Classes */
:deep(.ql-list-ordered) {
  list-style-type: decimal;
}

:deep(.ql-list-bullet) {
  list-style-type: disc;
}

/* Text Size Classes */
:deep(.ql-size-small) {
  /* Smaller font size */
}

:deep(.ql-size-large) {
  /* Larger font size */
}

:deep(.ql-size-huge) {
  /* Even larger font size */
}
</style>

<style lang="postcss" scoped>
#editor :deep(h1) {
  @apply text-2xl;
}

#editor :deep(h2) {
  @apply text-xl;
}

#editor :deep(h3) {
  @apply text-lg;
}

/*
  Enter and leave animations can use different
  durations and timing functions.
*/
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>
