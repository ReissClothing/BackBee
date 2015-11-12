<div class="row search-engine">
      <div class="col-bb5-x">
        <div class="row form-group"><span class="col-sm-6"><label for="form10" class="sr-only">{{ "title" | trans }}</label><input type="text" data-fieldName="title" class="form-control input-xs content-title" placeholder='{{ "title" | trans }}' id="form10"></span></div>
        <div class="row form-group">
          <div class="col-bb5-x">{{ "created_before" | trans }} : </div>
          <div class="col-bb5-22"><div class="input-group input-group-xs"><input type="text" data-fieldName="beforeDate" class="form-control disabled input-xs before-date bb5-datepicker" placeholder="dd/mm/aaaa"><span class="input-group-btn"><button class="btn btn-default show-calendar" type="button"><i class="fa fa-calendar"></i></button></span></div></div>
          <div class="col-bb5-x">{{ "created_after" | trans }} : </div>
          <div class="col-bb5-22"><div class="input-group input-group-xs"><input type="text" data-fieldName="afterDate" class="form-control disabled input-xs after-date bb5-datepicker" placeholder="dd/mm/aaaa"><span class="input-group-btn"><button class="btn btn-default show-calendar" type="button"><i class="fa fa-calendar"></i></button></span></div></div>
          <div class="col-bb5-x pull-right"><button class="btn btn-default btn-xs search-btn" type="button"><i class="fa fa-search"></i> {{"search" | trans}}</button></div>
        </div>
      </div>
    </div>
