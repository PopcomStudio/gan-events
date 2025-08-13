global.RepeaterManager = function RepeaterManager() {};
RepeaterManager.prototype = {};

RepeaterManager.prototype.init = function($holder) {

    this.$holder = $holder;

    this.limit = $holder.attr('data-limit') || -1;

    this.reorder = $holder.attr('data-reorder') === 'true';

    $holder.attr('repeaterInit', 'true');

    // Add button

    let btn = $('[data-action="add-after"][data-target="'+$holder.attr('id')+'"]');

    if (btn.length) {

        this.$addButton = btn.on('click', {manager: this}, this.addRow);

    } else {

        this.$addButton = $('<button/>')
            .attr('type', 'button')
            .attr('class', 'btn btn-sm btn-primary btn-icon-split shadow-sm')
            .attr('data-action', 'add-after')
            .html('<span class="icon"><i class="fas fa-plus text-white-50"></i></span><span class="text">Ajouter</span>')
            .on('click', {manager: this}, this.addRow)
            .appendTo(this.$holder);
    }

    // Rows location

    let rowsLocation = $('[data-rows="'+$holder.attr('id')+'"]');

    if (rowsLocation.length) {

        this.rowsLocation = rowsLocation;

    } else {

        this.rowsLocation = null;
    }

    this.regenValues();

    var manager = this;
    this.$rows.each(function(){

        $holder.trigger('prepare.row.repeater', [$(this)]);

        manager.initRow(manager, $(this));

        $holder.trigger('prepared.row.repeater', [$(this)]);
    });
};

RepeaterManager.prototype.initRow = function(manager, $row) {

    // Buttons
    if (manager.reorder) {

        manager.addButtons(manager, $row);
    }

    // Remove button
    $row.find('[data-delete-row]').on('click', {manager: manager}, manager.deleteRow)

    // Callback
    if (typeof manager.callback !== typeof undefined ) {

        manager.callback($row);
    }
};

RepeaterManager.prototype.addButtons = function(manager, $this) {

    var $upButton = $('<button/>')
        .attr('type', 'button')
        .attr('class', 'btn btn-sm btn-primary btn-icon-split shadow-sm')
        .attr('data-action', 'up')
        .attr('title', 'Monter')
        .html('<span class="icon"><i class="fas fa-arrow-up fa-sm text-white-50"></i></span><span class="text sr-only">Ajouter</span>')
        .on('click', {manager: manager}, manager.upRow);

    var $addButton = $('<button/>')
        .attr('type', 'button')
        .attr('class', 'btn btn-sm btn-primary btn-icon-split shadow-sm')
        .attr('data-action', 'add-before')
        .attr('title', 'Ajouter')
        .html('<span class="icon"><i class="fas fa-plus fa-sm text-white-50"></i></span><span class="text sr-only">Ajouter</span>')
        .on('click', {manager: manager}, manager.addRow);

    var $removeButton = $('<button/>')
        .attr('type', 'button')
        .attr('class', 'btn btn-sm btn-primary btn-icon-split shadow-sm')
        .attr('title', 'Supprimer')
        .attr('data-action', 'remove')
        .html('<span class="icon"><i class="fas fa-minus fa-sm text-white-50"></i></span><span class="text sr-only">Ajouter</span>')
        .on('click', {manager: manager}, manager.deleteRow);

    var $downButton = $('<button/>')
        .attr('type', 'button')
        .attr('class', 'btn btn-sm btn-primary btn-icon-split shadow-sm')
        .attr('data-action', 'down')
        .attr('title', 'Descendre')
        .html('<span class="icon"><i class="fas fa-arrow-down fa-sm text-white-50"></i></span><span class="text sr-only">Ajouter</span>')
        .on('click', {manager: manager}, manager.downRow);

    $('<div/>')
        .attr('class', 'buttons')
        .append($upButton)
        .append($addButton)
        .append($removeButton)
        .append($downButton)
        .appendTo($this);
}

RepeaterManager.prototype.regenValues = function() {

    if (this.$holder.data('rows')) {

        this.$rows = this.$holder.find(this.$holder.data('rows'));
        this.index = this.$rows.length;

    } else {

        this.$rows = this.$holder.children().not('button');
        this.index = this.$rows.length;
    }
};

RepeaterManager.prototype.upRow = function(e) {

    var $this = $(this).parent().parent();
    var manager = e.data.manager;
    var $prev = $this.prev();

    if ($prev.length) {

        $this.insertBefore($prev);
        manager.regenValues();
    }
};

RepeaterManager.prototype.downRow = function(e) {

    var $this = $(this).parent().parent();
    var manager = e.data.manager;
    var $next = $this.next();

    if ($next.prop('tagName') !== 'button') {

        $this.insertAfter($next);
        manager.regenValues();
    }
};

RepeaterManager.prototype.deleteRow = function(e) {

    e.data.manager.$holder.trigger('delete.repeater', [$(this).parent().parent()]);

    if (e.data.manager.$holder.data('rows')) {

        $(this).parents(e.data.manager.$holder.data('rows')).remove();

    } else {

        $(this).parent().parent().remove();
    }

    e.data.manager.regenValues();

    e.data.manager.$holder.trigger('deleted.repeater', [$(this).parent().parent()]);

    if (e.data.manager.$rows.length < e.data.manager.limit) e.data.manager.$addButton.show();
};

RepeaterManager.prototype.addRow = function(e) {

    e.data.manager.$holder.trigger('add.repeater', [$(this).parent().parent()]);

    var $this = $(this);

    // Get the data-prototype explained earlier
    var prototype = e.data.manager.$holder.data('prototype');
    var index = e.data.manager.index;

    // Replace '__name__' in the prototype's HTML to
    // instead be a number based on how many items we have
    prototype = prototype.replace(/__name__/g, index+1);

    var $row = $(prototype);

    if ($this.attr('data-action') == 'add-before') {

        // Display the row in the page, before row
        $this.parent().parent().before($row);

    } else {

        if (e.data.manager.rowsLocation) {

            e.data.manager.rowsLocation.append($row);

        } else {

            // Display the row in the page, before add button
            e.data.manager.$addButton.before($row);
        }
    }

    $row.find('.repeater').each(function(){
        var $this = $(this);
        var init = $this.attr('data-repeatInit');

        if (typeof init === typeof undefined || init === false) {

            var manager = new RepeaterManager();
            manager.init($(this));
        }
    });

    e.data.manager.initRow(e.data.manager, $row);
    e.data.manager.regenValues();

    e.data.manager.$holder.trigger('added.repeater', [$(this).parent().parent()]);

    if (e.data.manager.$rows.length >= e.data.manager.limit) e.data.manager.$addButton.hide();
};