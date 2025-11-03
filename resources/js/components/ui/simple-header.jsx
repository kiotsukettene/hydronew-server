import React from 'react'; 
import { Sheet, SheetContent, SheetFooter } from '@/components/ui/sheet';
import { Button, buttonVariants } from '@/components/ui/button';
import { MenuToggle } from '@/components/ui/menu-toggle';

export function SimpleHeader() {
	const [open, setOpen] = React.useState(false);

	const links = [
		{
			label: 'Features',
			href: '#',
		},
		{
			label: 'About',
			href: '#',
		},
		{
			label: 'FAQ',
			href: '#',
		},
		
	];

    return (
        <header
            className="fixed top-0 left-0 right-0 z-50 w-full px-6 py-3">
            <nav
                className="mx-auto flex h-12 max-w-fit items-center justify-between gap-8 rounded-full border bg-background/80 px-12 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-background/70">
				<div className="flex items-center gap-2">
<img src="/images/Logo.png" alt="Logo" className="h-6" />
				</div>
                <div className="hidden items-center gap-6 lg:flex">
					{links.map((link) => (
						<a className={buttonVariants({ variant: 'ghost' })} href={link.href}>
							{link.label}
						</a>
					))}
                    <Button size="sm" className='rounded-full'>Download</Button>
				</div>
				<Sheet open={open} onOpenChange={setOpen}>
                    <Button size="icon" variant="outline" className="lg:hidden rounded-full">
						<MenuToggle strokeWidth={2.5} open={open} onOpenChange={setOpen} className="size-6" />
					</Button>
					<SheetContent
                        className="bg-background/95 supports-[backdrop-filter]:bg-background/80 gap-0 backdrop-blur-lg"
                        showClose={false}
                        side="left">
						<div className="grid gap-y-2 overflow-y-auto px-4 pt-12 pb-5">
							{links.map((link) => (
								<a
                                    className={buttonVariants({
										variant: 'ghost',
										className: 'justify-start',
									})}
                                    href={link.href}>
									{link.label}
								</a>
							))}
						</div>
						<SheetFooter>
							<Button variant="outline">Sign In</Button>
							<Button>Get Started</Button>
						</SheetFooter>
					</SheetContent>
				</Sheet>
			</nav>
        </header>
    );
}
