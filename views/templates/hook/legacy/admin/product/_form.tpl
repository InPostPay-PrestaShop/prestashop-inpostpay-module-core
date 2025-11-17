{function inpostizi_form_label}
  <label for="{$form->vars.id}" class="form-control-label">
    {$form->vars.label}
    {if !empty($form->vars.help)}
      <span class="help-box" data-toggle="popover" data-trigger="hover" data-content="{$form->vars.help}"></span>
    {/if}
  </label>
{/function}

{function inpostizi_choice_widget}
  <select id="{$form->vars.id}" name="{$form->vars.full_name}" class="form-control custom-select">
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
{/function}

<div class="form-group col-md-4">
  {inpostizi_form_label form=$form.imageGalleryType}
  {inpostizi_choice_widget form=$form.imageGalleryType}
</div>
