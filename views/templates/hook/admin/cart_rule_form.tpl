{function inpostizi_form_label}
  <label for="{$form->vars.id}" class="control-label col-lg-3">
    {if !empty($form->vars.help)}
      <span class="label-tooltip" title="{$form->vars.help}">
        {$form->vars.label}
      </span>
    {else}
      {$form->vars.label}
    {/if}
  </label>
{/function}

{function inpostizi_switch_widget}
  <div class="col-lg-9">
    <span class="switch prestashop-switch fixed-width-lg">
      {foreach $form as $child}
        <input
          type="radio"
          name="{$form->vars.full_name}"
          id="{$child->vars.id}"
          value="{$child->vars.value}"
          {if $child->vars.checked} checked="checked"{/if}
        >
        <label class="t" for="{$child->vars.id}">{$child->vars.label}</label>
      {/foreach}

      <a class="slide-button btn"></a>
    </span>
  </div>
{/function}

{function inpostizi_choice_widget}
  <div class="col-lg-9">
    <select
      name="{$form->vars.full_name}"
      id="{$form->vars.id}"
    >
      {if isset($form->vars.placeholder)}
        <option value=""{if $form->vars.required && !$form->vars.value} selected="selected"{/if}>
          {$form->vars.placeholder}
        </option>
      {/if}

      {foreach $form->vars.choices as $choice}
        <option value="{$choice->value}"{if $form->vars.value === $choice->value} selected="selected"{/if}>
          {$choice->label}
        </option>
      {/foreach}
    </select>
  </div>
{/function}

<template id="inpostizi_form_tab">
  <div id="cart_rule_{$form->vars.id}" class="panel cart_rule_tab" style="display: none">
    <div class="form-group">
      {inpostizi_form_label form=$form.omnibus}
      {inpostizi_switch_widget form=$form.omnibus}
    </div>

    <div class="form-group">
      {inpostizi_form_label form=$form.promoDetailsPageId}
      {inpostizi_choice_widget form=$form.promoDetailsPageId}
    </div>
  </div>
</template>

<template id="inpostizi_nav_link">
  <li class="tab-row">
    <a id="cart_rule_link_{$form->vars.id}" class="tab-page" href="javascript:displayCartRuleTab('{$form->vars.id}');">
      <i class="icon-cog"></i>
      {$form->vars.label}
    </a>
  </li>
</template>
