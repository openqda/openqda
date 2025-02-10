> This is an opinionated structure of rules to create and refactor the backend logic, and it's subject to change and updates along the way.

Sources for these guidelines include:

- **Laravel Documentation:** [Laravel Latest Documentation](https://laravel.com/docs/latest)
- **Clean Code:** "Clean Code: A Handbook of Agile Software Craftsmanship" by Robert C. Martin [Clean Code - Google Books](https://www.google.com/books/edition/_/hjEFCAAAQBAJ?hl=en).

Laravel follows an MVC paradigm, with Models interacting directly with the database, Controllers processing the data and Views presenting the results.
Since we installed Laravel JetStream with Inertia, we use this for user registration, authentication, and team management.
Views in OpenQDA are Vue3 Pages where data is passed from PHP/Laravel to Inertia, then to the Vue3 view.

In general, every model has enabled CRUD operations, with permissions dictated by Policies located in `app/Policies`.

Controllers are located in the `app/Http/Controllers` folder.
Models are located in the `app/Models` folder.
Views, handled by Vue, are primarily located in `resources/js/Pages`.

## Controllers
Every controller's name reflects its purpose. For example, UserNavigationController manages user navigation, AnalysisController manages the analysis page, and ProjectController manages project-related resources. If a controller manages multiple models, the relationship will be indicated in the name, e.g., `CodebookCasesController.php` for a one-to-many relationship.

Our goal is to create a controller for each resource, but if a controller **needs** to manage multiple resources, this will be highlighted in its name.

When creating a function within a controller, we should first consider using or refactoring one of the following methods:
- `index`: List all the models in the database.
- `show`: Display a specific resource.
- `store`: Save the resource related to the model.
- `update`: Update the resource related to the model.
- `destroy`: Delete the resource related to the model.

If the function does not fit strictly within any of these methods, we can create a new function within the controller. If the function only edits a model, it should be placed in the model.

Using Form Requests to store validation rules outside the controllers is a good practice, providing a centralised way to validate and authorise requests.

## Models
Models represent the database connection related to a specific resource and manage relationships.
The model name is always singular.

- `$fillable`: Specifies which fields are "mass assignable," i.e., which can be filled or updated.
- `$timestamps`: A boolean indicating whether to save the `created_at`, `updated_at`, and `deleted_at` fields.
- `$primaryKey`: Indicates the database primary key field, used with `$incrementing` in UUIDs.
- `$auditExclude`: From the package "[Laravel Auditing](https://laravel-auditing.com/)" and specifies fields that should not be saved when recording actions on the model.
- `$casts`: Converts the type of a database field to a specific data type, e.g., JSON database column to array data type.
- `$withCount`: Attaches the count of related records.
- `$dispatchesEvents`: Specifies events fired during certain actions ('deleting', 'creating', etc.).

To get a specific attribute, Laravel allows the `get*SpecificName*Attribute` syntax, called "accessors". For example, to access a specific property inside the Codebook column:
```php
/**  
 * Accessor for 'public' attribute. 
 **/
public function getPublicAttribute()  
{  
    return $this->properties['sharedWithPublic'] ?? false;  
}
```

To set a specific attribute, Laravel allows the `set*SpecificName*Attribute` syntax, called "mutators". For example, to set a specific property inside the Codebook column:
```php
/**  
 * Mutator for 'public' attribute. 
 **/
public function setPublicAttribute($value)
{
    $this->properties['sharedWithPublic'] = $value;
}
```
Soft Deletes

Laravel provides a convenient way to handle “soft deleting” models so they are not actually removed from your database. Instead, a deleted_at timestamp is set on the record.

Usage:
```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}
```

Query Scopes

Laravel allows you to define query scopes to encapsulate common queries that you want to reuse throughout your application.

Local Scopes:
```php
public function scopeActive($query)
{
    return $query->where('active', 1);
}
```

Global Scopes are applied to the model **every time** a query is made.
To assign a global scope to a model, you may simply place the ScopedBy attribute on the model:
```php
<?php
 
namespace App\Models;
 
use App\Models\Scopes\AncientScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
 
#[ScopedBy([AncientScope::class])]
class User extends Model
{
    //
}
```
Or you can use the boot method in the model:

```php
protected static function boot()
{
    parent::boot();

    static::addGlobalScope('active', function (Builder $builder) {
        $builder->where('active', 1);
    });
}
```

## Conclusion

This document provides a structured set of guidelines and best practices for creating and refactoring backend logic in a Laravel application. While it covers many important aspects, it is not an exhaustive or conclusive guide. As we continue to develop and refine our application, these guidelines are subject to change and updates. 

We encourage all team members to stay informed about new developments and improvements in Laravel and related technologies. Continuous learning and adaptation are key to maintaining a robust and scalable codebase.

For any specific questions or suggestions for improvements, please feel free to discuss them with the team or propose changes via pull requests.
