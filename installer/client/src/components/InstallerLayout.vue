<!--
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */
 -->
<template>
  <div class="cia-ferias-installer">
    <div class="cia-ferias-installer-sidebar">
      <installer-steps
        :steps="steps"
        :current-step="currentStep"
      ></installer-steps>
      <div class="cia-ferias-installer-footer">
        <slot name="footer"></slot>
      </div>
    </div>
    <div class="cia-ferias-installer-header">
      <img :src="brandingSrc" width="280" alt="CIA Férias branding" />
    </div>
    <div class="cia-ferias-installer-content">
      <slot></slot>
    </div>
  </div>
</template>

<script>
import InstallerSteps from '@/components/InstallerSteps.vue';

export default {
  name: 'InstallerLayout',
  components: {
    'installer-steps': InstallerSteps,
  },
  props: {
    steps: {
      type: Array,
      required: true,
    },
    currentStep: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      brandingSrc: `${window.appGlobal.publicPath}/client/public/cia-ferias-brand.svg`,
    };
  },
};
</script>

<style src="./installer-layout.scss" lang="scss" scoped></style>
