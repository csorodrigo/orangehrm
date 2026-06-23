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
  <oxd-dialog class="cia-ferias-dialog-popup" @update:show="onClose">
    <div class="cia-ferias-modal-header">
      <oxd-text type="card-title">{{ $t('pim.import_details') }}</oxd-text>
    </div>
    <div class="cia-ferias-text-center-align">
      <oxd-text
        type="card-body"
        :class="{
          'cia-ferias-success-message': data.success > 0,
        }"
      >
        {{ $t('pim.n_records_successfully_imported', {count: data.success}) }}
      </oxd-text>
      <template v-if="data.failed > 0">
        <oxd-text type="card-body" class="cia-ferias-error-message">
          {{ $t('pim.n_records_failed_to_import', {count: data.failed}) }}
        </oxd-text>
        <oxd-text type="card-body" class="cia-ferias-error-message">
          {{ $t('pim.failed_rows') }}
        </oxd-text>
        <oxd-text type="card-body" class="cia-ferias-error-message">
          {{ data.failedRows.toString() }}
        </oxd-text>
      </template>
    </div>
    <div class="cia-ferias-modal-footer">
      <oxd-button
        display-type="secondary"
        :label="$t('general.ok')"
        @click="onClose"
      />
    </div>
  </oxd-dialog>
</template>

<script>
import {OxdDialog} from '@cia-ferias/oxd';

export default {
  name: 'EmployeeDataImportModal',
  components: {
    'oxd-dialog': OxdDialog,
  },
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
  emits: ['close'],
  methods: {
    onClose() {
      this.$emit('close', true);
    },
  },
};
</script>

<style lang="scss" scoped>
.cia-ferias-modal-header {
  display: flex;
  margin-bottom: 1.2rem;
  justify-content: center;
}
.cia-ferias-modal-footer {
  display: flex;
  margin-top: 1.2rem;
  justify-content: center;
}
.cia-ferias-text-center-align {
  text-align: center;
  overflow-wrap: break-word;
}
::v-deep(.cia-ferias-dialog-popup) {
  width: 450px;
}
.cia-ferias-success-message {
  color: $oxd-feedback-success-color;
}
.cia-ferias-error-message {
  color: $oxd-feedback-danger-color;
}
</style>
