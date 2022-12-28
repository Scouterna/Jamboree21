This directory contains local NPM package patches.

 - The patches are automatically maintained and applied during installation, by use of NPM patch-package
   (https://www.npmjs.com/package/patch-package).

 - If we need local changes towards an NPM package, then we should use this system to avoid checking in
   the full results and to reduce the maintenance problem.

 - Please refer to the patch-package documentation on how to create new patches.



Create a new patch
------------------
To patch an NPM package, do as follows.

1. Change any package sources inside directory node_modules.

2. Run:

   $ npx patch-package <package name>

3. Make sure that all new patch files created in patches are committed to the Git repository.


Example:

   $ emacs node_modules/slideout/index.js

   $ npx patch-package slideout

   patch-package 6.2.2
   • Creating temporary folder
   • Installing slideout@1.0.1 with npm
   • Diffing your files with clean files
   ✔ Created file patches/slideout+1.0.1.patch


Procedure to change a patch
---------------------------
A patch is made towards a specific version of an external NPM package. This means that we in general will have
one patch towards for example version 1.0.1 of package "slideout". We should never change the generated patch files,
instead they should be regenerated again.

If we need to make changes to one of our patched package, then do as follows.

1. Change the package inside directory node_modules.

2. Run:

   $ npx patch-package <package name>

3. Make sure that all changed patch files in patches are committed to the Git repository.
