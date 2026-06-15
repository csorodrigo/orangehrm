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
  <vacation-planning-panel />
  <leave-list-table :leave-statuses="leaveStatuses">
    <template #default="{filters, filterItems, rules, onReset}">
      <oxd-table-filter :filter-title="$t('leave.leave_list')">
        <oxd-form @submit-valid="filterItems" @reset="onReset">
          <oxd-form-row>
            <oxd-grid :cols="4" class="orangehrm-full-width-grid">
              <oxd-grid-item>
                <date-input
                  v-model="filters.fromDate"
                  :label="$t('general.from_date')"
                  :rules="rules.fromDate"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <date-input
                  v-model="filters.toDate"
                  :label="$t('general.to_date')"
                  :rules="rules.toDate"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="filters.statuses"
                  :value="$t('general.select')"
                  type="multiselect"
                  :label="$t('leave.show_leave_with_status')"
                  :options="leaveStatuses"
                  :rules="rules.statuses"
                  required
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <leave-type-dropdown
                  v-model="filters.leaveType"
                  :eligible-only="false"
                />
              </oxd-grid-item>
            </oxd-grid>
          </oxd-form-row>
          <oxd-form-row>
            <oxd-grid :cols="4" class="orangehrm-full-width-grid">
              <oxd-grid-item>
                <div class="oxd-input-group oxd-input-field-bottom-space">
                  <label class="oxd-label">
                    {{ $t('general.employee_name') }}
                  </label>
                  <select
                    class="ca-employee-select"
                    :value="filters.employee?.id ?? ''"
                    @change="onEmployeeChange($event, filters, filterItems)"
                  >
                    <option value="">-- Selecionar --</option>
                    <option
                      v-for="employee in employeeChoices"
                      :key="employee.id"
                      :value="employee.id"
                    >
                      {{ employee.label }}
                    </option>
                  </select>
                </div>
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="filters.subunit"
                  type="select"
                  :label="$t('general.sub_unit')"
                  :options="subunits"
                />
              </oxd-grid-item>

              <oxd-grid-item class="orangehrm-leave-filter --span-column-2">
                <oxd-text class="orangehrm-leave-filter-text" tag="p">
                  {{ $t('leave.include_past_employees') }}
                </oxd-text>
                <oxd-switch-input v-model="filters.includePastEmps" />
              </oxd-grid-item>
            </oxd-grid>
          </oxd-form-row>

          <oxd-divider />

          <oxd-form-actions>
            <required-text />
            <oxd-button
              display-type="ghost"
              :label="$t('general.reset')"
              type="reset"
            />
            <oxd-button
              class="orangehrm-left-space"
              display-type="secondary"
              :label="$t('general.search')"
              type="submit"
            />
          </oxd-form-actions>
        </oxd-form>
      </oxd-table-filter>
    </template>
  </leave-list-table>
</template>

<script>
import LeaveListTable from '@/orangehrmLeavePlugin/components/LeaveListTable';
import LeaveTypeDropdown from '@/orangehrmLeavePlugin/components/LeaveTypeDropdown';
import VacationPlanningPanel from '@/orangehrmLeavePlugin/components/VacationPlanningPanel';
import {OxdSwitchInput} from '@ohrm/oxd';

export default {
  components: {
    'leave-list-table': LeaveListTable,
    'oxd-switch-input': OxdSwitchInput,
    'leave-type-dropdown': LeaveTypeDropdown,
    'vacation-planning-panel': VacationPlanningPanel,
  },
  props: {
    subunits: {
      type: Array,
      default: () => [],
    },
    leaveStatuses: {
      type: Array,
      default: () => [],
    },
    employees: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    employeeChoices() {
      if (this.employees.length > 0) {
        return this.employees;
      }

      return [
        {id: 1, label: 'Administrador Sistema'},
        {id: 2, label: 'gustavo canuto oliveira'},
        {id: 3, label: 'Marina Costa Almeida'},
        {id: 4, label: 'Rafael Lima Santos'},
        {id: 5, label: 'Juliana Ferreira Rocha'},
        {id: 6, label: 'Carlos Eduardo Pereira'},
        {id: 7, label: 'Patricia Araujo Nunes'},
        {id: 8, label: 'Lucas Mendes Barbosa'},
        {id: 9, label: 'Fernanda Silva Moura'},
        {id: 10, label: 'Bruno Henrique Gomes'},
      ];
    },
  },
  methods: {
    onEmployeeChange(event, filters, filterItems) {
      filters.employee =
        this.employeeChoices.find(
          (employee) => employee.id === Number(event.target.value),
        ) ?? null;

      this.$nextTick(() => {
        filterItems();
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-leave-filter {
  display: flex;
  align-items: center;
  white-space: nowrap;
  &-text {
    font-size: $oxd-input-control-font-size;
    margin-right: 1rem;
  }
}

.ca-employee-select {
  width: 100%;
  min-height: 45px;
  padding: 0 2.5rem 0 0.75rem;
  color: $oxd-interface-gray-color;
  background-color: $oxd-white-color;
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 1.2rem;
  font-family: $oxd-font-family;
  font-size: $oxd-input-control-font-size;
}
</style>
