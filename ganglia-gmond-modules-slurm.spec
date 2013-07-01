Name:		ganglia-gmond-modules-slurm
Version:	0.1
Release:	1%{?dist}
Summary:	Ganglia module for reporting SLURM worker stats

Group:		System Environment/Base
License:	BSD
URL:		https://github.com/jthiltges/ganglia-gmond-modules-slurm
Source0:	%{name}-%{version}.tar.bz2

BuildArch:	noarch
BuildRequires:	python-devel
Requires:	ganglia-gmond, python, ganglia-gmond-modules-python

# This package produces no debuginfo
%global debug_package %{nil}

%define conf_dir %{_sysconfdir}/ganglia

%description
Ganglia module for reporting SLURM worker stats

%prep
%setup -q

%build

%install

# Create the directory structure
%__install -d -m 0755 $RPM_BUILD_ROOT/%{conf_dir}/conf.d
%__install -d -m 0755 $RPM_BUILD_ROOT/%{_libdir}/ganglia/python_modules

# Install the files
pwd
%__cp -f python_modules/*.py $RPM_BUILD_ROOT%{_libdir}/ganglia/python_modules
%__cp -f conf.d/*.pyconf $RPM_BUILD_ROOT%{conf_dir}/conf.d

%files
%{_libdir}/ganglia/python_modules/*.py*
%config(noreplace) %{conf_dir}/conf.d/*.pyconf*

%changelog

